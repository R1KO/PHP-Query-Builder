<?php

namespace R1KO\QueryBuilder;

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
    /**
     * @var IConnection
     */
    private $db;

    /**
     * @var array
     */
    private $bind = [];

    /**
     * @var string
     */
    private $table;

    /**
     * @var null|string
     */
    private $alias;

    /**
     * @var array
     */
    private $join    = [];

    /**
     * @var array
     */
    private $select  = [];

    /**
     * @var array
     */
    private $where   = [];

    /**
     * @var array
     */
    private $groupBy = [];

    /**
     * @var array
     */
    private $having  = [];

    /**
     * @var array
     */
    private $order   = [];

    /**
     * @var null|int
     */
    private $limit;

    /**
     * @var null|int
     */
    private $offset;

    /**
     * @var bool
     */
    private $isDistinct;

    /**
     * @var array
     */
    private $fetchMode;

    /**
     * @var string|callable
     */
    private $asObject;

    /**
     * @var null|string
     */
    private $sql;

    /**
     * QueryBuilder constructor.
     * @param IConnection $db
     */
    public function __construct(IConnection $db)
    {
        $this->db = $db;
    }

    public function table(string $table, ?string $alias = null): IQueryBuilder
    {
        $this->table = $table;
        $this->alias = $alias;

        return $this;
    }

    public function from(string $table, ?string $alias = null): IQueryBuilder
    {
        return $this->table($table, $alias);
    }

    private function getConnection(): IConnection
    {
        return $this->db;
    }

    private function getDriver(): IDriver
    {
        return $this->getConnection()->getDriver();
    }

    private function getPlaceholder(string $column): string
    {
        return ':' . $column;
    }
    private function quoteTable(string $name): string
    {
        return $this->getDriver()->quoteTableName($name);
    }

    private function quoteColumn(string $name): string
    {
        return $this->getDriver()->quoteColumnName($name);
    }

    private function getCurrentTableName(): string
    {
        return $this->getQuotedTableName($this->table, $this->alias);
    }

    private function getQuotedTableName(string $table, ?string $alias = null): string
    {
        $table = $this->quoteColumn($table);
        if (!$alias) {
            return $table;
        }

        return $table . ' AS ' . $this->quoteColumn($alias);
    }

    private function getTable(): string
    {
        $table = $this->quoteColumn($this->table);
        if ($this->alias === null) {
            return $table;
        }

        return $this->quoteColumn($this->alias);
    }

    public function insert(array $values): int
    {
        $query = $this->getInsertSql(array_keys($values));

        $this->setSql($query);
        $this->db->execute($query, $values);

        return $this->db->getLastInsertId();
    }

    private function getInsertSql(array $schema): string
    {
        $columns = [];
        $placeholders = [];

        foreach ($schema as $column) {
            $columns[] = $this->quoteColumn($column);
            $placeholders[] = $this->getPlaceholder($column);
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s);',
            $this->quoteTable($this->table),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
    }

    public function update(array $values): int
    {
        $query = sprintf(
            'UPDATE %s SET ',
            $this->quoteTable($this->table),
        );

        $bindings = [];
        $fields = [];

        foreach ($values as $column => $value) {
            $fields[] = sprintf('%s = ?', $this->quoteColumn($column));
            $bindings[] = $value;
        }

        $query .= implode(', ', $fields);

        $whereSql = $this->getWhereSql($bindings);

        if ($whereSql) {
            $query .= $whereSql;
        }

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->rowCount();
    }

    public function delete(): int
    {
        $query = sprintf(
            'DELETE FROM %s',
            $this->quoteTable($this->table),
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

    public function insertBatch(array $values): int
    {
        if (count($values) === 0) {
            throw new BuilderException('Empty insert values');
        }

        $bindings = [];
        $query = $this->getBatchInsertSql($values, $bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->rowCount();
    }

    private function getBatchInsertSql(array $values, array &$bindings): string
    {
        $columns = array_map(function ($column) {
            return $this->quoteColumn($column);
        }, array_keys($values[0]));

        $placeholderGroups = [];

        foreach ($values as $i => $row) {
            $placeholders = [];
            foreach ($row as $columnName => $columnValue) {
                $paramName = $columnName . $i;
                $bindings[$paramName] = $columnValue;
                $placeholders[] = $this->getPlaceholder($paramName);
            }
            $placeholderGroups[] = '(' . implode(', ', $placeholders) . ')';
        }

        $columnsSql = implode(', ', $columns);
        $valuesSql = implode(', ', $placeholderGroups);

        return sprintf(
            'INSERT INTO %s (%s) VALUES %s;',
            $this->quoteTable($this->table),
            $columnsSql,
            $valuesSql
        );
    }

    public function insertMass(array $values, bool $useTransaction = false): array
    {
        if (count($values) === 0) {
            throw new BuilderException('Empty insert values');
        }

        $query = $this->getInsertSql(array_keys($values[0]));

        $this->setSql($query);

        $iterator = function () use ($values): iterable {
            foreach ($values as $row) {
                yield $row;
            }
        };

        $executor = function () use ($query, $iterator): array {

            $results = [];
            foreach ($this->db->executeIterable($query, $iterator()) as $result) {
                $results[] = $result;
            }

            return $results;
        };

        if ($useTransaction) {
            return $this->db->transaction($executor);
        }

        return $executor();
    }

    public function insertIterable(array $schema, iterable $iterator, bool $useTransaction = false): iterable
    {
        $query = $this->getInsertSql($schema);

        $this->setSql($query);

        if ($useTransaction) {
            yield from $this->db->transaction(function () use ($query, $iterator): iterable {
                yield from $this->db->executeIterable($query, $iterator);
            });
            return;
        }

        yield from $this->db->executeIterable($query, $iterator);
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

    public function distinct(): IQueryBuilder
    {
        $this->isDistinct = true;

        return $this;
    }

    /**
     * @param array $params
     * @return IQueryBuilder
     */
    public function fetchMode(...$params): IQueryBuilder
    {
        $this->fetchMode = $params;

        return $this;
    }

    /**
     * @param string|object $arg
     * @return IQueryBuilder
     */
    public function as($object = \stdClass::class): IQueryBuilder
    {
        $this->asObject = $object;

        return $this;
    }

    private function getFetchParams(/*PDOStatement $statement*/): array
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

    // http://phpfaq.ru/pdo/fetch
    // https://prowebmastering.ru/php-pdo-konstanty-fetch.html

    /**
     * @param string|RawExpression|null $column
     * @return mixed
     */
    public function getOne($column = null)
    {
        if ($column !== null) {
            $this->select([$column]);
        }
        $bindings = [];
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetchColumn();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param string|RawExpression|null $column
     * @return mixed
     */
    public function getCol(?string $column = null): array
    {
        if ($column !== null) {
            $this->select([$column]);
        }

        $bindings = [];
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param string|RawExpression|null $column
     * @return mixed
     */
    public function getColIterable(?string $column = null): iterable
    {
        if ($column !== null) {
            $this->select([$column]);
        }

        $bindings = [];
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
        $this->limit = 1;
        $bindings = [];
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
        $bindings = [];
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * @param string|RawExpression|null $columnName
     * @return array
     */
    public function getAssoc(string $columnName = null): array
    {
        $bindings = [];
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
     * @param string|RawExpression|null $columnName
     * @return array
     */
    public function getIterable(string $columnName = null): iterable
    {
        $bindings = [];
        $query = $this->getSelectSql($bindings);

        $this->setSql($query);
        $statement = $this->db->execute($query, $bindings);

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($columnName === null) {
                yield $row;
                continue;
            }
            $key = $columnName ?? array_key_first($row);
            yield $row[$key] => $row;
        }
    }

    /**
     * @param string|null $columnName
     * @return int
     */
    public function count(?string $columnName = null): int
    {
        if ($columnName !== null) {
            $columnName = $this->getQuotedColumnName($columnName);
            if ($this->isDistinct) {
                $this->isDistinct = false;
                $columnName = 'DISTINCT ' . $columnName;
            }
        } else {
            $columnName = '*';
        }

        $columnName = $this->raw('COUNT(' . $columnName . ')');

        return (int) $this->getOne($columnName);
    }

    /**
     * @param string|RawExpression $columnName
     * @return int|float
     */
    public function sum($columnName)
    {
        if (is_string($columnName)) {
            $columnName = $this->getQuotedColumnName($columnName);
        } elseif ($columnName instanceof RawExpression) {
            $columnName = $columnName->get();
        }

        $columnName = $this->raw('SUM(' . $columnName . ')');

        return $this->getOne($columnName) ?: 0;
    }

    /**
     * @param string|RawExpression $columnName
     * @return int|float
     */
    public function avg($columnName)
    {
        if (is_string($columnName)) {
            $columnName = $this->getQuotedColumnName($columnName);
        } elseif ($columnName instanceof RawExpression) {
            $columnName = $columnName->get();
        }

        $columnName = $this->raw('AVG(' . $columnName . ')');

        return $this->getOne($columnName) ?: 0;
    }

    /**
     * @param string|RawExpression $columnName
     * @return int|float
     */
    public function min($columnName)
    {
        if (is_string($columnName)) {
            $columnName = $this->getQuotedColumnName($columnName);
        } elseif ($columnName instanceof RawExpression) {
            $columnName = $columnName->get();
        }

        $columnName = $this->raw('MIN(' . $columnName . ')');

        return $this->getOne($columnName);
    }

    /**
     * @param string|RawExpression $columnName
     * @return int|float
     */
    public function max($columnName)
    {
        if (is_string($columnName)) {
            $columnName = $this->getQuotedColumnName($columnName);
        } elseif ($columnName instanceof RawExpression) {
            $columnName = $columnName->get();
        }

        $columnName = $this->raw('MAX(' . $columnName . ')');

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

        $query = '';

        foreach ($this->join as $join) {
            $table = $this->getQuotedTableName($join['table'], $join['alias']);

            $query .= ' ' . strtoupper($join['type']) . ' JOIN ' . $table;

//            var_dump($join['condition']);

            // TODO: Remake to WHERE Conditions
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
            $this->getCurrentTableName()
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

        var_dump($query);
        var_dump($bindings);

        return $query . ';';
    }

    private function getSelectColumnsSql(): string
    {
        if (count($this->select) === 0) {
            return '*';
        }

        $columns = [];

        foreach ($this->select as $index => $column) {
            $alias = null;
            if (is_string($index)) {
                $alias = $column;
                $column = $index;
            }

            $columns[] = $this->getPreparedColumnName($column, $alias);
        }

        return implode(', ', $columns);
    }

    /**
     * @param string|RawExpression $column
     * @param string|null $alias
     * @return string
     */
    private function getPreparedColumnName($column, ?string $alias = null): string
    {
        if ($column instanceof RawExpression) {
            return $column->get();
        }

        if (is_string($column)) {
            return $this->getQuotedColumnName($column, $alias);
        }

        return '';
    }

    private function getQuotedColumnName(string $column, ?string $alias = null): string
    {
        if (strpos($column, '.') !== false) {
            [$table, $columnName] = explode('.', $column);
            $table = $this->quoteTable($table);
        } else {
            $columnName = $column;
            $table = $this->getTable();
        }

        if ($columnName !== '*') {
            $columnName = $this->quoteColumn($columnName);

            if ($alias !== null) {
                $columnName .= ' AS ' . $this->quoteColumn($alias);
            }
        }

        return $table . '.' . $columnName;
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
        if (!$this->limit) {
            return null;
        }

        $query = ' LIMIT ?';
        $bindings[] = $this->limit;
        if ($this->offset) {
            $query .= ' OFFSET ?';
            $bindings[] = $this->offset;
        }

        return $query;
    }

    /**
     * @return string|null
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    private function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    private function getWhereSql(array &$bindings): ?string
    {
        if ($this->where) {
            $sql = $this->where->getConditionsSql($bindings);

            if ($sql) {
                return ' WHERE ' . $sql;
            }
        }

        return null;
    }

    private function getHavingSql(array &$bindings): ?string
    {
        if ($this->having) {
            $sql = $this->having->getConditionsSql($bindings);

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
    public function groupBy(...$columns): IQueryBuilder
    {
        // TODO: may be use strict args type
        array_push($this->groupBy, ...$this->getFlatArray($columns));

        return $this;
    }

    /**
     * @param string|Closure $condition
     * @return $this
     */
    public function having(...$condition): IQueryBuilder
    {
        $this->getHavingBuilder()->and(...$condition);

        return $this;
    }

    /**
     * @param string|Closure $condition
     * @return $this
     */
    public function orHaving(...$condition): IQueryBuilder
    {
        $this->getHavingBuilder()->or(...$condition);

        return $this;
    }

    /**
     * @param string|Closure $condition
     * @return $this
     */
    public function where(...$condition): IQueryBuilder
    {
        $this->getWhereBuilder()->and(...$condition);

        return $this;
    }

    /**
     * @param string|Closure $condition
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
        if (!$this->where) {
            $this->where = $this->getConditionsBuilder();
        }

        return $this->where;
    }

    /**
     * @return ConditionsBuilder
     */
    private function getHavingBuilder(): ConditionsBuilder
    {
        if (!$this->having) {
            $this->having = $this->getConditionsBuilder();
        }

        return $this->having;
    }

    /**
     * @return ConditionsBuilder
     */
    private function getConditionsBuilder(): ConditionsBuilder
    {
        return new ConditionsBuilder();
    }

    public static function createRaw(string $expression): RawExpression
    {
        return new RawExpression($expression);
    }

    public function raw(string $expression): RawExpression
    {
        return static::raw($expression);
    }

    private function getFlatArray(array $input): array
    {
        if (count($input) === 0) {
            return $input;
        }

        $result = [];

        foreach ($input as $value) {
            if (!is_array($value)) {
                $result[] = $value;
                continue;
            }

            array_push($result, ...$this->getFlatArray($value));
        }

        return $result;
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

    private function getOperator(string $name): string
    {
        $name
    }
}
