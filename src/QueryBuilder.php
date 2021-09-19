<?php

namespace R1KO\QueryBuilder;

use R1KO\Database\Contracts\IConnection;
use R1KO\Database\Contracts\IDriver;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\Contracts\IExpression;
use R1KO\QueryBuilder\Exceptions\BuilderException;
use R1KO\QueryBuilder\ConditionsBuilder;
use R1KO\QueryBuilder\Expressions\Column;
use R1KO\QueryBuilder\Expressions\Raw;
use PDO;
use PDOStatement;
use Closure;
use R1KO\QueryBuilder\Expressions\Table;

class QueryBuilder implements IQueryBuilder
{
    private IConnection $db;
    private string $table;
    /** @var callable $tableSub */
    private $tableSub;
    private ?string $alias = null;
    private array $join;
    private array $select;
    private ConditionsBuilder $where;
    private array $groupBy;
    private ConditionsBuilder $having;
    private array $order;
    private int $limit;
    private int $offset;
    private bool $isDistinct = false;
    /** @var string|callable */
    private $asObject;

    private ?string $sql;
    private array $bindings = [];

    /**
     * @param IConnection $db
     */
    public function __construct(IConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return $this
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
     * @return $this
     */
    public function from(string $table, ?string $alias = null): IQueryBuilder
    {
        return $this->table($table, $alias);
    }

    /**
     * @param callable $table
     * @param string $alias
     * @return $this
     */
    public function fromSub(callable $table, string $alias): IQueryBuilder
    {
        $this->tableSub = $table;
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return IConnection
     */
    public function getConnection(): IConnection
    {
        return $this->db;
    }

    /**
     * @return IDriver
     */
    private function getDriver(): IDriver
    {
        return $this->getConnection()->getDriver();
    }

    /**
     * @param string $name
     * @return string
     */
    private function quoteTable(string $name): string
    {
        return $this->getDriver()->quoteTableName($name);
    }

    /**
     * @param string $name
     * @return string
     */
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

    /**
     * @return $this
     */
    protected function createSelfInstance(): IQueryBuilder
    {
        return new self($this->db);
    }

    /**
     * @param array $bindings
     * @return string
     * @throws BuilderException
     */
    protected function getTableNameForSelect(array &$bindings): string
    {
        if (isset($this->tableSub)) {
            $sql = $this->getSubQuerySelectSql($this->tableSub, $bindings);

            return sprintf('%s AS %s', $sql, $this->alias);
        }

        return $this->getQuotedTableName($this->getTable(), $this->alias);
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return string
     */
    private function getQuotedTableName(string $table, ?string $alias = null): string
    {
        $table = $this->quoteColumn($table);
        if (!$alias) {
            return $table;
        }

        return $table . ' AS ' . $this->quoteColumn($alias);
    }

    /**
     * @return string
     * @throws BuilderException
     */
    private function getTableForSelect(): string
    {
        if ($this->alias) {
            return $this->quoteColumn($this->alias);
        }

        return $this->quoteColumn($this->getTable());
    }

    /**
     * @param array $values
     */
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

    /**
     * @param array $values
     */
    public function insertWithSub(array $values): void
    {
        $bindings = [];
        $query = $this->getInsertSqlWithSubQueries($values, $bindings);

        $this->setSql($query);
        $this->db->execute($query, $bindings);
    }

    /**
     * @param callable $subQuery
     * @param array $bindings
     * @param bool $addBrackets
     * @return string
     */
    public function getSubQuerySelectSql(callable $subQuery, array &$bindings, bool $addBrackets = true): string
    {
        $builder = $this->createSelfInstance();
        $subQuery($builder);

        $sql = $builder->getSelectSql($bindings);

        return $addBrackets ? sprintf('(%s)', $sql) : $sql;
    }

    /**
     * @param array $schema
     * @param array $bindings
     * @return string
     * @throws BuilderException
     */
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

    /**
     * @param array $schema
     * @return string
     * @throws BuilderException
     */
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
        $bindings = &$this->bindings;
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

    /**
     * @param array $values
     * @return int
     * @throws BuilderException
     */
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

    /**
     * @param array $values
     * @return string
     * @throws BuilderException
     */
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

    /**
     * @param array $values
     * @param bool $useTransaction
     * @throws BuilderException
     */
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

    /**
     * @param array $schema
     * @param iterable $iterator
     * @param bool $useTransaction
     * @throws BuilderException
     */
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

    /**
     * @param array $values
     * @return int
     */
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

    /**
     * @param array $schema
     * @return string
     * @throws BuilderException
     */
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

    /**
     * @param array $values
     * @return int
     */
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

    /**
     * @param array $values
     * @param array $bindings
     * @return string
     * @throws BuilderException
     */
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

    /**
     * @return int
     * @throws BuilderException
     */
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

    /**
     * @param array|string[] $columns
     * @return $this
     */
    public function select(array $columns = ['*']): IQueryBuilder
    {
        $this->select = $columns;

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function addSelect(array $columns): IQueryBuilder
    {
        array_push($this->select, ...$columns);

        return $this;
    }

    /**
     * @param bool $state
     * @return $this
     */
    public function distinct(bool $state = true): IQueryBuilder
    {
        $this->isDistinct = $state;

        return $this;
    }

    /**
     * @param string|object $arg
     * @return $this
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
     * @param string|IExpression|null $column
     * @return mixed
     * @throws BuilderException
     */
    public function getOne($column = null)
    {
        if ($column !== null) {
            $this->select([$column]);
        }
        $bindings = &$this->bindings;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);

        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetchColumn();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param string|IExpression|null $column
     * @return array
     * @throws BuilderException
     */
    public function getCol(?string $column = null): array
    {
        if ($column !== null) {
            $this->select([$column]);
        } elseif (!isset($this->select) || count($this->select) === 0) {
            throw new BuilderException('No column specified');
        }

        $bindings = &$this->bindings;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param string|IExpression|null $column
     * @return mixed
     * @throws BuilderException
     */
    public function getColIterable($column = null): iterable
    {
        if ($column !== null) {
            $this->select([$column]);
        } elseif (!isset($this->select) || count($this->select) === 0) {
            // TODO: unit tests
            throw new BuilderException('No column specified');
        }

        $bindings = &$this->bindings;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        while ($value = $statement->fetch(PDO::FETCH_COLUMN)) {
            yield $value;
        }
    }

    /**
     * @return array|null
     * @throws BuilderException
     */
    public function getRow(): ?array
    {
        $this->limit(1);
        $bindings = &$this->bindings;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result ? : null;
    }

    /**
     * @return array|null
     * @throws BuilderException
     */
    public function getAll(): ?array
    {
        $bindings = &$this->bindings;
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result ? : null;
    }

    /**
     * @param string|IExpression|null $columnName
     * @return array
     */
    public function getAssoc(string $columnName = null): array
    {
        // TODO: check if column exists in select (or alias)
        $bindings = &$this->bindings;
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
     * @param string|IExpression|null $columnName
     * @return array
     */
    public function getIterable(string $columnName = null): iterable
    {
        // TODO: check if column exists in select (or alias)
        $bindings = &$this->bindings;
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
     * @param string|IExpression $column
     * @return IExpression
     * @throws BuilderException
     */
    private function getAggregateColumnSql(string $function, $column): IExpression
    {
        if ($column instanceof IExpression) {
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
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function sum($columnName)
    {
        $columnName = $this->getAggregateColumnSql('SUM', $columnName);

        return $this->getOne($columnName) ? : 0;
    }

    /**
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function avg($columnName)
    {
        $columnName = $this->getAggregateColumnSql('AVG', $columnName);

        return $this->getOne($columnName) ? : 0;
    }

    /**
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function min($columnName)
    {
        $columnName = $this->getAggregateColumnSql('MIN', $columnName);

        return $this->getOne($columnName);
    }

    /**
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function max($columnName)
    {
        $columnName = $this->getAggregateColumnSql('MAX', $columnName);

        return $this->getOne($columnName);
    }

    /**
     * @param string $table
     * @param array|callable $conditions
     * @return $this
     */
    public function join(string $table, $conditions): IQueryBuilder
    {
        return $this->innerJoin($table, $conditions);
    }

    /**
     * @param string $table
     * @param array|callable $conditions
     * @return $this
     */
    public function innerJoin(string $table, $conditions): IQueryBuilder
    {
        return $this->addJoin('inner', $table, $conditions);
    }

    /**
     * @param string $table
     * @param array|callable $conditions
     * @return $this
     */
    public function leftJoin(string $table, $conditions): IQueryBuilder
    {
        return $this->addJoin('left', $table, $conditions);
    }

    /**
     * @param string $table
     * @param array|callable $conditions
     * @return $this
     */
    public function rightJoin(string $table, $conditions): IQueryBuilder
    {
        return $this->addJoin('right', $table, $conditions);
    }

    /**
     * @param string $table
     * @param array|callable $conditions
     * @return $this
     */
    public function fullJoin(string $table, $conditions): IQueryBuilder
    {
        // TODO: unit tests
        return $this->addJoin('full', $table, $conditions);
    }

    /**
     * @param string $table
     * @return $this
     */
    public function crossJoin(string $table): IQueryBuilder
    {
        // TODO: unit tests
        return $this->addJoin('cross', $table);
    }

    // TODO: add [inner|left|right|cross|full]JoinSub

    /**
     * @param string $type
     * @param string $table
     * @param array|callable|null $conditions
     * @return $this
     */
    protected function addJoin(string $type, string $table, $conditions = null): IQueryBuilder
    {
        // TODO: move to factory method
        $table = $this->createTableExpression($table);

        $this->join[] = [
            'type'       => $type,
            'table'      => $table->getTable(),
            'alias'      => $table->getAlias(),
            'conditions' => $conditions,
        ];

        return $this;
    }

    /**
     * @return string|null
     * @throws BuilderException
     */
    private function getJoinSql(): ?string
    {
        if (!isset($this->join) || count($this->join) === 0) {
            return null;
        }

        // TODO: remake condition to use ConditionsBuilder
        $joins = [];

        foreach ($this->join as $join) {
            $type = strtoupper($join['type']);
            $table = $this->getQuotedTableName($join['table'], $join['alias']);

            if (!$join['conditions']) {
                $joins[] = sprintf('%s JOIN %s', $type, $table);
                continue;
            }

            if (is_array($join['conditions'])) {
                $conditionBuilder = new ConditionsBuilder($this);
                foreach ($join['conditions'] as $firstColumn => $secondColumn) {
                    $conditionBuilder->and($firstColumn, $this->createColumnExpression($secondColumn));
                }
                $conditions = $conditionBuilder->getSql($this->bindings);
            } elseif (is_callable($join['conditions'])) {
                $builder = $this->createSelfInstance();
                $condition = $join['conditions'];
                $condition($builder);
                $conditions = $builder->where->getSql($this->bindings);
            } else {
                throw new BuilderException('Incorrect type of argument');
            }

            $joins[] = sprintf(
                '%s JOIN %s ON (%s)',
                $type,
                $table,
                $conditions
            );
        }

        return implode(' ', $joins);
    }

    public function whereRaw(string $value): IQueryBuilder
    {
        // TODO: add bindings
        $value = new Raw($value);
        $this->getWhereBuilder()->and($value);
        return $this;
    }

    public function orWhereRaw(string $value): IQueryBuilder
    {
        // TODO: add bindings
        $value = new Raw($value);
        $this->getWhereBuilder()->or($value);
        return $this;
    }

    public function whereColumn(string $column, string $value): IQueryBuilder
    {
        $value = $this->createColumnExpression($value);
        $this->getWhereBuilder()->and($column, $value);
        return $this;
    }

    public function orWhereColumn(string $column, string $value): IQueryBuilder
    {
        $value = $this->createColumnExpression($value);
        $this->getWhereBuilder()->or($column, $value);
        return $this;
    }

    /**
     * @param array $bindings
     * @return string
     * @throws BuilderException
     */
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

    /**
     * @return string
     * @throws BuilderException
     */
    private function getSelectColumnsSql(): string
    {
        if (!isset($this->select) || count($this->select) === 0) {
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
     * @param string|callable|IExpression $column
     * @param string|null $alias
     * @return string
     */
    private function getPreparedColumnName($column, ?string $alias = null): string
    {
        if (is_string($column)) {
            return $this->getQuotedColumnName($column, $alias);
        }

        if ($column instanceof IExpression) {
            $columnName = $column->get();

            if ($alias !== null) {
                $columnName .= ' AS ' . $this->quoteColumn($alias);
            }

            return $columnName;
        }

        if (is_callable($column)) {
            $sql = $this->getSubQuerySelectSql($column, $this->bindings);

            return $alias ? sprintf('%s AS %s', $sql, $alias) : $sql;
        }

        throw new BuilderException('Incorrect type of column');
    }

    /**
     * @param string $column
     * @param string|null $alias
     * @return string
     * @throws BuilderException
     */
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

    /**
     * @return string|null
     * @throws BuilderException
     */
    private function getGroupBySql(): ?string
    {
        if (!isset($this->groupBy)) {
            return null;
        }

        $columns = array_map(function ($column): string {
            return $this->getQuotedColumnName($column);
        }, $this->groupBy);

        return ' GROUP BY ' . implode(', ', $columns);
    }

    /**
     * @return string|null
     * @throws BuilderException
     */
    private function getOrderBySql(): ?string
    {
        if (!isset($this->order)) {
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

    /**
     * @param array $bindings
     * @return string|null
     */
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

    /**
     * @return string|null
     */
    private function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     */
    private function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    /**
     * @return array|null
     */
    private function getBindings(): ?array
    {
        return $this->bindings;
    }

    public function getConditionsSql(array &$bindings): ?string
    {
        if (isset($this->where)) {
            return $this->where->getSql($bindings);
        }

        return null;
    }

    /**
     * @param array $bindings
     * @return string|null
     */
    private function getWhereSql(array &$bindings): ?string
    {
        if (!isset($this->where)) {
            return null;
        }

        $sql = $this->where->getSql($bindings);

        return ' WHERE ' . $sql;
    }

    /**
     * @param array $bindings
     * @return string|null
     */
    private function getHavingSql(array &$bindings): ?string
    {
        if (!isset($this->having)) {
            return null;
        }

        $sql = $this->having->getSql($bindings);

        return ' HAVING ' . $sql;
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

    /**
     * @param string $column
     * @param string $dir
     * @return $this
     */
    public function orderBy(string $column, $dir = 'ASC'): IQueryBuilder
    {
        $this->order[$column] = strtoupper($dir);

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderAsc(string $column): IQueryBuilder
    {
        $this->orderBy($column, 'ASC');

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
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

    /**
     * @param string $table
     * @return Table
     */
    private function createTableExpression(string $table): Table
    {
        return new Table($table, $this->getConnection());
    }

    /**
     * @param string $column
     * @return Column
     */
    private function createColumnExpression(string $column): Column
    {
        return new Column($column, $this->getConnection());
    }

    /**
     * @param string $expression
     * @return IExpression
     */
    public static function asRaw(string $expression): IExpression
    {
        return new Raw($expression);
    }

    /**
     * @param string $expression
     * @return IExpression
     */
    public function raw(string $expression): IExpression
    {
        return static::asRaw($expression);
    }
}
