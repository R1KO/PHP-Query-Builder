<?php

namespace R1KO\QueryBuilder\Contracts;

use Closure;
use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Contracts\IExpression;

interface IQueryBuilder
{
    //    public function getSql(): ?string;
    //    public function getBindings(): ?array;
    /**
     * @param string $expression
     * @return \R1KO\QueryBuilder\Contracts\IExpression
     */
    public static function asRaw(string $expression): IExpression;

    /**
     * @param string $expression
     * @return \R1KO\QueryBuilder\Contracts\IExpression
     */
    public function raw(string $expression): IExpression;

    /**
     * @return IConnection
     */
    public function getConnection(): IConnection;

    /**
     * @param callable $subQuery
     * @param array $bindings
     * @param bool $addBrackets
     * @return string
     */
    public function getSubQuerySelectSql(callable $subQuery, array &$bindings, bool $addBrackets = true): string;

    /**
     * @param string $table
     * @param string|null $alias
     * @return IQueryBuilder
     */
    public function table(string $table, ?string $alias = null): IQueryBuilder;

    /**
     * @param string $table
     * @param string|null $alias
     * @return IQueryBuilder
     */
    public function from(string $table, ?string $alias = null): IQueryBuilder;

    /**
     * @param callable $table
     * @param string $alias
     * @return IQueryBuilder
     */
    public function fromSub(callable $table, string $alias): IQueryBuilder;

    /**
     * @param array $values
     */
    public function insert(array $values): void;

    /**
     * @param array $values
     * @param string|null $aiColumn
     * @return int
     */
    public function insertGetId(array $values, ?string $aiColumn = null): int;

    /**
     * @param array $values
     */
    public function insertWithSub(array $values): void;

    /**
     * @param array $columns
     * @param callable $from
     */
    public function insertFrom(array $columns, callable $from): void;

    /**
     * @param array $values
     * @return int
     */
    public function insertBatch(array $values): int;

    /**
     * @param array $values
     * @param bool $useTransaction
     */
    public function insertMass(array $values, bool $useTransaction = false): void;

    /**
     * @param array $schema
     * @param iterable $iterator
     * @param bool $useTransaction
     */
    public function insertIterable(array $schema, iterable $iterator, bool $useTransaction = false): void;

    /**
     * @param array $values
     * @return int
     */
    public function update(array $values): int;

    /**
     * @param array $values
     * @return int
     */
    public function updateWithSub(array $values): int;

    /**
     * @return int
     */
    public function delete(): int;

    /**
     * @param array $columns
     * @return IQueryBuilder
     */
    public function select(array $columns): IQueryBuilder;

    /**
     * @param array $columns
     * @return IQueryBuilder
     */
    public function addSelect(array $columns): IQueryBuilder;

    /**
     * @param bool $state
     * @return IQueryBuilder
     */
    public function distinct(bool $state = true): IQueryBuilder;

    /**
     * @param string|IExpression|null $column
     * @return mixed
     */
    public function getOne($column = null);

    /**
     * @param string|IExpression|null $column
     * @return array
     */
    public function getCol(?string $column = null): array;

    /**
     * @param string|IExpression|null $column
     * @return mixed
     */
    public function getColIterable(?string $column = null): iterable;

    /**
     * @return array|null
     */
    public function getRow(): ?array;

    /**
     * @return array|null
     */
    public function getAll(): ?array;

    /**
     * @param string|IExpression|null $columnName
     * @return array
     */
    public function getAssoc(string $columnName = null): array;

    /**
     * @param string|IExpression|null $columnName
     * @return array
     */
    public function getIterable(string $columnName = null): iterable;

    /**
     * @param string|null $columnName
     * @return int
     */
    public function count(?string $columnName = null): int;

    /**
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function sum($columnName);

    /**
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function avg($columnName);

    /**
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function min($columnName);

    /**
     * @param string|IExpression $columnName
     * @return int|float
     */
    public function max($columnName);

    /**
     * @param string $table
     * @param array $conditions
     * @return IQueryBuilder
     */
    public function join(string $table, array $conditions): IQueryBuilder;

    /**
     * @param string $table
     * @param array $conditions
     * @return IQueryBuilder
     */
    public function innerJoin(string $table, array $conditions): IQueryBuilder;

    /**
     * @param string $table
     * @param array $conditions
     * @return IQueryBuilder
     */
    public function leftJoin(string $table, array $conditions): IQueryBuilder;

    /**
     * @param string $table
     * @param array $conditions
     * @return IQueryBuilder
     */
    public function rightJoin(string $table, array $conditions): IQueryBuilder;

    /**
     * @param string $table
     * @param array $conditions
     * @return IQueryBuilder
     */
    public function fullJoin(string $table, array $conditions): IQueryBuilder;

    /**
     * @param string $table
     * @return IQueryBuilder
     */
    public function crossJoin(string $table): IQueryBuilder;

    /**
     * @param int $limit
     * @return IQueryBuilder
     */
    public function limit(int $limit): IQueryBuilder;

    /**
     * @param int $offset
     * @return IQueryBuilder
     */
    public function offset(int $offset): IQueryBuilder;

    /**
     * @param string $column
     * @param string $dir
     * @return IQueryBuilder
     */
    public function orderBy(string $column, $dir = 'ASC'): IQueryBuilder;

    /**
     * @param string $column
     * @return IQueryBuilder
     */
    public function orderAsc(string $column): IQueryBuilder;

    /**
     * @param string $column
     * @return IQueryBuilder
     */
    public function orderDesc(string $column): IQueryBuilder;

    /**
     * @param array $columns
     * @return $this
     */
    public function groupBy(array $columns): IQueryBuilder;

    /**
     * @param array $condition
     * @return $this
     */
    public function having(...$condition): IQueryBuilder;

    /**
     * @param array $condition
     * @return $this
     */
    public function orHaving(...$condition): IQueryBuilder;

    /**
     * @param array $condition
     * @return $this
     */
    public function where(...$condition): IQueryBuilder;

    /**
     * @param array $condition
     * @return $this
     */
    public function orWhere(...$condition): IQueryBuilder;
}
