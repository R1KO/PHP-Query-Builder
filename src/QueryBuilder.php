<?php

namespace R1KO\QueryBuilder;

use Faker\Generator;
use R1KO\Database\Contracts\IConnection;
use R1KO\Database\Contracts\IDriver;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\Exceptions\BuilderException;
use R1KO\QueryBuilder\ConditionsBuilder;
use R1KO\QueryBuilder\Expressions\Raw;
use PDO;
use PDOStatement;
use Closure;

class QueryBuilder implements IQueryBuilder
{
    private IConnection $db;
    private array $bind = [];
    private string $table;
    /** @var callable $tableSub */
    private $tableSub;
    private ?string $alias = null;
    private array $join = [];
    private array $select = [];
    private ConditionsBuilder $where;
    private array $groupBy = [];
    private ConditionsBuilder $having;
    private array $order = [];
    private int $limit;
    private int $offset;
    private bool $isDistinct = false;
    /** @var string|callable */
    private $asObject;

    private ?string $sql;
    private array $bindings = [];

    public function __construct(IConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return IQueryBuilder
     */
    public function table(string $table, ?string $alias = null): IQueryBuilder
    {
        $this->table = $table;
        $this->alias = $alias;
        return $this;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return IQueryBuilder
     */
    public function from(string $table, ?string $alias = null): IQueryBuilder
    {
        return $this->table($table, $alias);
    }

    /**
     * @param callable $table
     * @param string $alias
     * @return IQueryBuilder
     */
    public function fromSub(callable $table, string $alias): IQueryBuilder
    {
        $this->tableSub = $table;
        $this->alias = $alias;
        return $this;
    }

    public function getConnection(): IConnection
    {
        return $this->db;
    }

    private function getDriver(): IDriver
    {
        return $this->getConnection()->getDriver();
    }

    private function quoteTable(string $name): string
    {
        return $this->getDriver()->quoteTableName($name);
    }

    private function quoteColumn(string $name): string
    {
        return $this->getDriver()->quoteColumnName($name);
    }

    /**
     * @return string
     * @throws BuilderException
     */
    protected function getTable(): string
    {
        if (!isset($this->table)) {
            throw new BuilderException('No table specified!');
        }

        return $this->table;
    }

    protected function createSelfInstance(): IQueryBuilder
    {
        return new self($this->db);
    }

    protected function getTableNameForSelect(array &$bindings): string
    {
        if (isset($this->tableSub)) {
            $sql = $this->getSubQuerySelectSql($this->tableSub, $bindings);

            return sprintf('%s AS %s', $sql, $this->alias);
        }

        return $this->getQuotedTableName($this->getTable(), $this->alias);
    }

    private function getQuotedTableName(string $table, ?string $alias = null): string
    {
        $table = $this->quoteColumn($table);
        if (!$alias) {
            return $table;
        }

        return $table . ' AS ' . $this->quoteColumn($alias);
    }

    private function getTableForSelect(): string
    {
        if ($this->alias) {
            return $this->quoteColumn($this->alias);
        }

        return $this->quoteColumn($this->getTable());
    }

    public function insert(array $values): void
    {
        $query = $this->getInsertSql(array_keys($values));
        $bindings = array_values($values);

        $this->setSql($query);
        $this->db->execute($query, $bindings);
    }

    /**
     * @param array $values
     * @param string|null $aiColumn
     * @return int
     */
    public function insertGetId(array $values, ?string $aiColumn = null): int
    {
        $query = $this->getInsertSql(array_keys($values));

        $this->setSql($query);

        $bindings = array_values($values);
        $this->db->execute($query, $bindings);
        $id = $this->db->getLastInsertId($aiColumn);

        return is_numeric($id) ? (int) $id : $id;
    }

    public function insertWithSub(array $values): void
    {
        $bindings = [];
        $query = $this->getInsertSqlWithSubQueries($values, $bindings);

        $this->setSql($query);
        $this->db->execute($query, $bindings);
    }

    public function getSubQuerySelectSql(callable $subQuery, array &$bindings, bool $addBrackets = true): string
    {
        $builder = $this->createSelfInstance();
        $subQuery($builder);

        $sql = $builder->getSelectSql($bindings);

        return $addBrackets ? sprintf('(%s)', $sql) : $sql;
    }

    private function getInsertSqlWithSubQueries(array $schema, array &$bindings): string
    {
        $columns = [];
        $placeholders = [];

        foreach ($schema as $column => $value) {
            $columns[] = $this->quoteColumn($column);

            if (!is_callable($value)) {
                $bindings[] = $value;
                $placeholders[] = '?';
                continue;
            }

            $placeholders[] = $this->getSubQuerySelectSql($value, $bindings);
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s);',
            $this->quoteTable($this->getTable()),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
    }

    private function getInsertSql(array $schema): string
    {
        $columns = array_map(function ($column) {
            return $this->quoteColumn($column);
        }, $schema);

        $placeholders = array_pad([], count($schema), '?');

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s);',
            $this->quoteTable($this->getTable()),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
    }

    /**
     * @param array $columns
     * @param callable $from
     * @return void
     */
    public function insertFrom(array $columns, callable $from): void
    {
        $bindings = &$this->bind;
        $sql = $this->getSubQuerySelectSql($from, $bindings, false);

        $columnNames = [];

        foreach ($columns as $column) {
            $columnNames[] = $this->quoteColumn($column);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) %s',
            $this->quoteTable($this->getTable()),
            implode(', ', $columnNames),
            $sql
        );

        $this->setSql($sql);
        $this->db->execute($sql, $bindings);
    }

    public function insertBatch(array $values): int
    {
        if (count($values) === 0) {
            throw new BuilderException('Empty insert values');
        }

        $bindings = [];

        foreach ($values as $row) {
            array_push($bindings, ...array_values($row));
        }
        $query = $this->getBatchInsertSql($values);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->rowCount();
    }

    private function getBatchInsertSql(array $values): string
    {
        $firstRow = $values[array_key_first($values)];

        $columns = array_map(function ($column) {
            return $this->quoteColumn($column);
        }, array_keys($firstRow));
        $columnsCount = count($columns);

        $placeholders = [];

        foreach ($values as $row) {
            $rowPlaceholder = array_pad([], $columnsCount, '?');
            $placeholders[] = sprintf('(%s)', implode(', ', $rowPlaceholder));
        }

        $columnsSql = implode(', ', $columns);
        $valuesSql = implode(', ', $placeholders);

        return sprintf(
            'INSERT INTO %s (%s) VALUES %s;',
            $this->quoteTable($this->getTable()),
            $columnsSql,
            $valuesSql
        );
    }

    public function insertMass(array $values, bool $useTransaction = false): void
    {
        if (count($values) === 0) {
            throw new BuilderException('Empty insert values');
        }

        $query = $this->getInsertSql(array_keys($values[array_key_first($values)]));

        $values = array_map(function ($row) {
            return array_values($row);
        }, $values);

        $this->setSql($query);

        $executor = function () use ($query, $values): void {
            $this->db->executeIterable($query, $values);
        };

        if ($useTransaction) {
            $this->db->transaction($executor);
            return;
        }

        $executor();
    }

    public function insertIterable(array $schema, iterable $iterator, bool $useTransaction = false): void
    {
        $query = $this->getInsertSql($schema);

        $this->setSql($query);

        $normalizedIterator = static function () use ($iterator): iterable {
            foreach ($iterator as $row) {
                yield array_values($row);
            }
        };

        if ($useTransaction) {
            $this->db->transaction(function () use ($query, $normalizedIterator): void {
                $this->db->executeIterable($query, $normalizedIterator());
            });
            return;
        }

        $this->db->executeIterable($query, $normalizedIterator());
    }

    public function update(array $values): int
    {
        $query = $this->getUpdateSql(array_keys($values));
        $bindings = array_values($values);

        $whereSql = $this->getWhereSql($bindings);
        if ($whereSql) {
            $query .= $whereSql;
        }

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->rowCount();
    }

    private function getUpdateSql(array $schema): string
    {
        $columns = array_map(function ($column) {
            return sprintf('%s = ?', $this->quoteColumn($column));
        }, $schema);

        return sprintf(
            'UPDATE %s SET %s',
            $this->quoteTable($this->getTable()),
            implode(', ', $columns)
        );
    }

    public function updateWithSub(array $values): int
    {
        $bindings = [];
        $query = $this->getUpdateSqlWithSubQueries($values, $bindings);

        $whereSql = $this->getWhereSql($bindings);
        if ($whereSql) {
            $query .= $whereSql;
        }

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->rowCount();
    }

    private function getUpdateSqlWithSubQueries(array $values, array &$bindings): string
    {
        $columns = [];

        foreach ($values as $column => $value) {
            if (!is_callable($value)) {
                // TODO: unit tests
                $columns[] = sprintf('%s = ?', $this->quoteColumn($column));
                $bindings[] = $value;
                continue;
            }

            $subSql = $this->getSubQuerySelectSql($value, $bindings);
            $columns[] = sprintf('%s = %s', $this->quoteColumn($column), $subSql);
        }

        return sprintf(
            'UPDATE %s SET %s',
            $this->quoteTable($this->getTable()),
            implode(', ', $columns)
        );
    }

    public function delete(): int
    {
        $query = sprintf(
            'DELETE FROM %s',
            $this->quoteTable($this->getTable()),
        );

        $bindings = [];
        $whereSql = $this->getWhereSql($bindings);

        if ($whereSql) {
            $query .= $whereSql;
        }

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->rowCount();
    }

    public function select(array $columns): IQueryBuilder
    {
        $this->select = $columns;

        return $this;
    }

    public function addSelect(array $columns): IQueryBuilder
    {
        array_push($this->select, ...$columns);

        return $this;
    }

    public function distinct(bool $state = true): IQueryBuilder
    {
        $this->isDistinct = $state;

        return $this;
    }

    /**
     * @param string|object $arg
     * @return IQueryBuilder
     */
    public function as($object = \stdClass::class): IQueryBuilder
    {
        // TODO: unit tests
        $this->asObject = $object;

        return $this;
    }

/*
    // http://phpfaq.ru/pdo/fetch
    // https://prowebmastering.ru/php-pdo-konstanty-fetch.html
    private function getFetchParams(
        //PDOStatement $statement
    ): array
    {
//        if ($this->fetchMode) {
//            $statement->setFetchMode(...$this->fetchMode);
//            return;
//        }

        if ($this->asObject) {
            $mode = PDO::FETCH_ASSOC;

            if (is_string($this->asObject)) {
                $mode = PDO::FETCH_CLASS;
            } elseif (is_object($this->asObject)) {
                $mode = PDO::FETCH_INTO;
            }
//            $statement->setFetchMode($mode, $this->asObject);

            return [$mode, $this->asObject];
        }

        return [PDO::FETCH_ASSOC];
    }
*/

    /**
     * @param string|Raw|null $column
     * @return mixed
     */
    public function getOne($column = null)
    {
        if ($column !== null) {
            $this->select([$column]);
        }
        $bindings = &$this->bind;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);

        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetchColumn();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param string|Raw|null $column
     * @return array
     */
    public function getCol(?string $column = null): array
    {
        if ($column !== null) {
            $this->select([$column]);
        } elseif (count($this->select) === 0) {
            throw new BuilderException('No column specified');
        }

        $bindings = &$this->bind;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param string|Raw|null $column
     * @return mixed
     */
    public function getColIterable($column = null): iterable
    {
        if ($column !== null) {
            $this->select([$column]);
        } elseif (count($this->select) === 0) {
            // TODO: unit tests
            throw new BuilderException('No column specified');
        }

        $bindings = &$this->bind;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        while ($value = $statement->fetch(PDO::FETCH_COLUMN)) {
            yield $value;
        }
    }

    /**
     * @return array|null
     */
    public function getRow(): ?array
    {
        $this->limit(1);
        $bindings = &$this->bind;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result ?: null;
    }

    /**
     * @return array|null
     */
    public function getAll(): ?array
    {
        $bindings = &$this->bind;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * @param string|Raw|null $columnName
     * @return array
     */
    public function getAssoc(string $columnName = null): array
    {
        // TODO: check if column exists in select (or alias)
        $bindings = &$this->bind;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        $results = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $key = $columnName ?? array_key_first($row);
            $results[$row[$key]] = $row;
        }

        return $results;
    }

    /**
     * @param string|Raw|null $columnName
     * @return array
     */
    public function getIterable(string $columnName = null): iterable
    {
        // TODO: check if column exists in select (or alias)
        $bindings = &$this->bind;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($columnName === null) {
                yield $row;
                continue;
            }
            yield $row[$columnName] => $row;
        }
    }

    /**
     * @param string|null $columnName
     * @return int
     */
    public function count(?string $columnName = null): int
    {
        if ($columnName === null) {
            $columnName = '*';
            $this->isDistinct = false;
        }

        $columnName = $this->getAggregateColumnSql('COUNT', $columnName);

        return (int) $this->getOne($columnName);
    }

    /**
     * @param string $function
     * @param string|Raw $column
     * @return string
     */
    private function getAggregateColumnSql(string $function, $column): Raw
    {
        if ($column instanceof Raw) {
            $column = $column->get();
        } elseif (is_string($column) && $column !== '*') {
            $column = $this->getQuotedColumnName($column);
        }

        $distinct = '';
        if ($this->isDistinct) {
            $this->distinct(false);
            $distinct = 'DISTINCT ';
        }

        return $this->raw(sprintf('%s(%s%s)', $function, $distinct, $column));
    }

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function sum($columnName)
    {
        $columnName = $this->getAggregateColumnSql('SUM', $columnName);

        return $this->getOne($columnName) ?: 0;
    }

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function avg($columnName)
    {
        $columnName = $this->getAggregateColumnSql('AVG', $columnName);

        return $this->getOne($columnName) ?: 0;
    }

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function min($columnName)
    {
        $columnName = $this->getAggregateColumnSql('MIN', $columnName);

        return $this->getOne($columnName);
    }

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function max($columnName)
    {
        $columnName = $this->getAggregateColumnSql('MAX', $columnName);

        return $this->getOne($columnName);
    }

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function join($table, array $condition): IQueryBuilder
    {
        return $this->innerJoin($table, $condition);
    }

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function innerJoin($table, array $condition): IQueryBuilder
    {
        return $this->addJoin('inner', $table, $condition);
    }

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function leftJoin($table, array $condition): IQueryBuilder
    {
        return $this->addJoin('left', $table, $condition);
    }

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function rightJoin($table, array $condition): IQueryBuilder
    {
        return $this->addJoin('right', $table, $condition);
    }
    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function fullJoin($table, array $condition): IQueryBuilder
    {
        // TODO: unit tests
        return $this->addJoin('full', $table, $condition);
    }

    /**
     * @param string       $type
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    protected function addJoin(string $type, $table, array $condition): IQueryBuilder
    {
        $alias = null;
        $tableName = $table;
        if (is_array($table)) {
            $tableName = array_key_first($table);
            $alias = $table[$tableName];
        }

        $this->join[] = [
            'type'      => $type,
            'table'     => $tableName,
            'alias'     => $alias,
            'condition' => $condition,
        ];

        return $this;
    }

    private function getJoinSql(): ?string
    {
        if (count($this->join) === 0) {
            return null;
        }

        // TODO: remake condition to use ConditionsBuilder
        $query = '';

        foreach ($this->join as $join) {
            $table = $this->getQuotedTableName($join['table'], $join['alias']);

            $query .= ' ' . strtoupper($join['type']) . ' JOIN ' . $table;

            $conditions = [];
            foreach ($join['condition'] as $col1 => $col2) {
                $conditions[] = $this->getQuotedColumnName($col1) . ' = ' . $this->getQuotedColumnName($col2);
            }

            $query .= ' ON ' . implode(' AND ', $conditions);
        }

        return $query;
    }

    private function getSelectSql(array &$bindings = array()): string
    {
        $columns = $this->getSelectColumnsSql();

        if ($this->isDistinct) {
            $columns = 'DISTINCT ' . $columns;
        }

        $query = sprintf(
            'SELECT %s FROM %s',
            $columns,
            $this->getTableNameForSelect($bindings)
        );

        $join = $this->getJoinSql();

        if ($join) {
            $query .= $join;
        }

        $whereSql = $this->getWhereSql($bindings);

        if ($whereSql) {
            $query .= $whereSql;
        }

        $groupBySql = $this->getGroupBySql();

        if ($groupBySql) {
            $query .= $groupBySql;
        }

        $havingSql = $this->getHavingSql($bindings);

        if ($havingSql) {
            $query .= $havingSql;
        }

        $orderBy = $this->getOrderBySql();
        if ($orderBy) {
            $query .= $orderBy;
        }

        $limit = $this->getLimitSql($bindings);
        if ($limit) {
            $query .= $limit;
        }

        return $query;
    }

    private function getSelectColumnsSql(): string
    {
        if (count($this->select) === 0) {
            return '*';
        }

        $columns = [];

        foreach ($this->select as $alias => $column) {
            if (!is_string($alias)) {
                $alias = null;
            }

            $columns[] = $this->getPreparedColumnName($column, $alias);
        }

        return implode(', ', $columns);
    }

    /**
     * @param string|callable|Raw $column
     * @param string|null $alias
     * @return string
     */
    private function getPreparedColumnName($column, ?string $alias = null): string
    {
        if (is_string($column)) {
            return $this->getQuotedColumnName($column, $alias);
        }

        if ($column instanceof Raw) {
            $columnName = $column->get();

            if ($alias !== null) {
                $columnName .= ' AS ' . $this->quoteColumn($alias);
            }

            return $columnName;
        }

        if (is_callable($column)) {
            $sql = $this->getSubQuerySelectSql($column, $this->bind);

            return $alias ? sprintf('%s AS %s', $sql, $alias) : $sql;
        }

        throw new BuilderException('Incorrect type of column');
    }

    private function getQuotedColumnName(string $column, ?string $alias = null): string
    {
        if (strpos($column, '.') !== false) {
            [$table, $columnName] = explode('.', $column);
            $table = $this->quoteTable($table);
        } else {
            $columnName = $column;

            $table = $this->getTableForSelect();
        }

        if ($columnName == '*') {
            return $table . '.' . $columnName;
        }

        $columnName = $table . '.' . $this->quoteColumn($columnName);

        if ($alias !== null) {
            $columnName .= ' AS ' . $this->quoteColumn($alias);
        }

        return $columnName;
    }

    private function getGroupBySql(): ?string
    {
        if ($this->groupBy) {
            $columns = array_map(function ($column): string {
                return $this->getQuotedColumnName($column);
            }, $this->groupBy);

            return ' GROUP BY ' . implode(', ', $columns);
        }

        return null;
    }

    private function getOrderBySql(): ?string
    {
        if (!$this->order) {
            return null;
        }

        $orderBy = array_map(
            function ($column, $dir) {
                return $this->getQuotedColumnName($column) . ' ' . $dir;
            },
            array_keys($this->order),
            $this->order
        );

        return ' ORDER BY ' . implode(', ', $orderBy);
    }

    private function getLimitSql(array &$bindings): ?string
    {
        if (!isset($this->limit)) {
            return null;
        }

        $query = ' LIMIT ?';
        $bindings[] = $this->limit;
        if (isset($this->offset)) {
            $query .= ' OFFSET ?';
            $bindings[] = $this->offset;
        }

        return $query;
    }

    private function getSql(): ?string
    {
        return $this->sql;
    }

    private function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    private function getBindings(): ?array
    {
        return $this->bind;
    }

    private function getWhereSql(array &$bindings): ?string
    {
        if (isset($this->where)) {
            $sql = $this->where->getSql($bindings);

            if ($sql) {
                return ' WHERE ' . $sql;
            }
        }

        return null;
    }

    private function getHavingSql(array &$bindings): ?string
    {
        if (isset($this->having)) {
            $sql = $this->having->getSql($bindings);

            if ($sql) {
                return ' HAVING ' . $sql;
            }
        }

        return null;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): IQueryBuilder
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): IQueryBuilder
    {
        $this->offset = $offset;

        return $this;
    }

    public function orderBy(string $column, $dir = 'ASC'): IQueryBuilder
    {
        $this->order[$column] = strtoupper($dir);

        return $this;
    }

    public function orderAsc(string $column): IQueryBuilder
    {
        $this->orderBy($column, 'ASC');

        return $this;
    }

    public function orderDesc(string $column): IQueryBuilder
    {
        $this->orderBy($column, 'DESC');

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function groupBy(array $columns): IQueryBuilder
    {
        $this->groupBy = $columns;

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function addGroupBy(array $columns): IQueryBuilder
    {
        array_push($this->groupBy, ...$columns);

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function having(...$condition): IQueryBuilder
    {
        $this->getHavingBuilder()->and(...$condition);

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function orHaving(...$condition): IQueryBuilder
    {
        $this->getHavingBuilder()->or(...$condition);

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function where(...$condition): IQueryBuilder
    {
        $this->getWhereBuilder()->and(...$condition);

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function orWhere(...$condition): IQueryBuilder
    {
        $this->getWhereBuilder()->or(...$condition);

        return $this;
    }
/*
    public function __call(string $name, array $arguments): IQueryBuilder
    {
        if (strncmp($name, 'where') === 0) {
            return $this->where(str_replace($name, 'where', ''), ...$arguments);
        }
        if (strncmp($name, 'orWhere') === 0) {
            return $this->orWhere(...$arguments);
        }

        return $this;
    }


    public function __call(string $name, array $arguments)
    {
        if (strncmp($name, 'where', 5) === 0) {
            $this->getWhereBuilder()->and($this->getOperator(substr($name, 5)), ...$arguments);
        }
        if (strncmp($name, 'orWhere', 7) === 0) {
            $this->getWhereBuilder()->or($this->getOperator(substr($name, 7)), ...$arguments);
        }
    }
*/
    // whereIn
    // whereNotIn
    // whereNull
    // whereNotNull
    // whereBetween
    // whereNotBetween

    /**
     * @return ConditionsBuilder
     */
    private function getWhereBuilder(): ConditionsBuilder
    {
        if (!isset($this->where)) {
            $this->where = $this->createConditionsBuilder();
        }

        return $this->where;
    }

    /**
     * @return ConditionsBuilder
     */
    private function getHavingBuilder(): ConditionsBuilder
    {
        if (!isset($this->having)) {
            $this->having = $this->createConditionsBuilder();
        }

        return $this->having;
    }

    /**
     * @return ConditionsBuilder
     */
    private function createConditionsBuilder(): ConditionsBuilder
    {
        return new ConditionsBuilder($this);
    }

    public static function asRaw(string $expression): Raw
    {
        return new Raw($expression);
    }

    public function raw(string $expression): Raw
    {
        return static::asRaw($expression);
    }
}
