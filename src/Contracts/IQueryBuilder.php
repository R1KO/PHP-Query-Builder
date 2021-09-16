<?php

namespace R1KO\QueryBuilder\Contracts;

use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Expressions\Raw;

interface IQueryBuilder
{
//    public function getSql(): ?string;
//    public function getBindings(): ?array;
    public static function asRaw(string $expression): Raw;
    public function raw(string $expression): Raw;
    public function getConnection(): IConnection;
    public function getSubQuerySelectSql(callable $subQuery, array &$bindings, bool $addBrackets = true): string;

    public function table(string $table, ?string $alias = null): IQueryBuilder;
    public function from($table, ?string $alias = null): IQueryBuilder;
    public function insert(array $values): void;
    public function insertGetId(array $values, ?string $aiColumn = null): int;
    public function insertWithSub(array $values): void;
    public function insertFrom(array $columns, callable $from): void;
    public function insertBatch(array $values): int;
    public function insertMass(array $values, bool $useTransaction = false): void;
    public function insertIterable(array $schema, iterable $iterator, bool $useTransaction = false): void;

    public function update(array $values): int;
    public function updateWithSub(array $values): int;

    public function delete(): int;

    public function select(array $columns): IQueryBuilder;
    public function addSelect(array $columns): IQueryBuilder;

    public function distinct(bool $state = true): IQueryBuilder;

    /**
     * @param string|Raw|null $column
     * @return mixed
     */
    public function getOne($column = null);

    /**
     * @param string|Raw|null $column
     * @return array
     */
    public function getCol(?string $column = null): array;

    /**
     * @param string|Raw|null $column
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
     * @param string|Raw|null $columnName
     * @return array
     */
    public function getAssoc(string $columnName = null): array;

    /**
     * @param string|Raw|null $columnName
     * @return array
     */
    public function getIterable(string $columnName = null): iterable;

    /**
     * @param string|null $columnName
     * @return int
     */
    public function count(?string $columnName = null): int;

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function sum($columnName);

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function avg($columnName);

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function min($columnName);

    /**
     * @param string|Raw $columnName
     * @return int|float
     */
    public function max($columnName);

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function join($table, array $condition): IQueryBuilder;

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function innerJoin($table, array $condition): IQueryBuilder;

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function leftJoin($table, array $condition): IQueryBuilder;

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function rightJoin($table, array $condition): IQueryBuilder;

    /**
     * @param string|array $table
     * @param array        $condition
     * @return IQueryBuilder
     */
    public function fullJoin($table, array $condition): IQueryBuilder;

    public function limit(int $limit): IQueryBuilder;
    public function offset(int $offset): IQueryBuilder;

    public function orderBy(string $column, $dir = 'ASC'): IQueryBuilder;
    public function orderAsc(string $column): IQueryBuilder;
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
