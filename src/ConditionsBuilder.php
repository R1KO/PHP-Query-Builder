<?php

namespace R1KO\QueryBuilder;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\Exceptions\ConditionException;
use R1KO\QueryBuilder\Expressions\Raw;
use Closure;

class ConditionsBuilder
{
    public const SQL_AND = ' AND ';
    public const SQL_OR = ' OR ';

    private array $conditions = [];
    private array $bindings = [];
    private IQueryBuilder $builder;

    final public function __construct(IQueryBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function where(...$condition)
    {
        $this->conditions[] = [static::SQL_AND, $condition];

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function orWhere(...$condition)
    {
        $this->conditions[] = [static::SQL_OR, $condition];

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function and(...$condition)
    {
        $this->conditions[] = [static::SQL_AND, $condition];

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function or(...$condition)
    {
        $this->conditions[] = [static::SQL_OR, $condition];

        return $this;
    }

    /**
     * @param array $bindings
     * @return string|null
     */
    public function getConditionsSql(array &$bindings = []): ?string
    {
        $parts = [];
        $this->bindings = &$bindings;

        foreach ($this->conditions as $index => $conditionClause) {
            [$type, $condition] = $conditionClause;
            $sql = $this->getSubConditionsSql($condition);

            if ($index === 0) {
                $parts[] = $sql;
                continue;
            }

            $parts[] = $type . $sql;
        }

        return implode('', $parts);
    }

    /**
     * @param array $condition
     * @return string
     */
    private function getSubConditionsSql(array $condition): string
    {
        $count = count($condition);
        if ($count === 0) {
            throw new ConditionException('Incorrect number of arguments');
        }

        if ($count === 1) {
            $condition = array_shift($condition);

            if ($condition instanceof Closure) {
                $query = new static($this->builder);
                $condition($query);

                return '(' . $query->getConditionsSql($this->bindings) . ')';
            }

            if (is_array($condition)) {
                $conditions = [];
                foreach ($condition as $column => $value) {
                    $conditions[] = $this->getSubConditionsSql([$column, $value]);
                }

                return implode(static::SQL_AND, $conditions);
            }

            if ($condition instanceof Raw) {
                return sprintf('(%s)', $condition);
            }

            throw new ConditionException('Incorrect type of argument #1');
        }

        if ($count === 2) {
            // equals ( = )
            [$column, $value] = $condition;
            $operator = '=';
            if (strpos($column, ' ') !== false) {
                [$column, $operator] = explode(' ', $column, 2);
            }
            $column = trim($column);

            if (is_callable($value)) {
                $value = $this->builder->getSubQuerySelectSql($value, $this->bindings);

                $operator = $this->getPreparedOperator($operator);
                return implode(' ', [$column, $operator, $value]);
            }

            return $this->getConditionSql($column, $operator, $value);
        }

        [$column, $operator, $value] = $condition;
        $column = $this->getPreparedColumn($column);
        return $this->getConditionSql($column, $operator, $value);
    }

    /**
     * @param string|Raw $value
     * @return bool
     */
    private function isRaw($value): bool
    {
        return $value instanceof Raw;
    }

    private function getPreparedColumn($column): string
    {
        if ($this->isRaw($column)) {
            return $column->get();
        }

        return $this->quoteColumn(trim($column));
    }

    private function quoteColumn(string $name): string
    {
        return $this->builder->getConnection()->getDriver()->quoteColumnName($name);
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @return string
     */
    private function getConditionSql(string $column, string $operator, $value): string
    {
        $operator = $this->getPreparedOperator($operator);
        $value = $this->getValueByOperator($operator, $value);
        return implode(' ', [$column, $operator, $value]);
    }

    private function getPreparedOperator(string $operator): string
    {
        return strtoupper(trim($operator));
    }

    /**
     * @param string $operator
     * @param mixed  $value
     * @return string
     */
    private function getValueByOperator(string $operator, $value): string
    {
        switch ($operator) {
            case 'IS':
            case 'IS NOT':
                return 'NULL';

            case 'IN':
            case 'NOT IN':
                $placeholders = [];
                foreach ($value as $index => $inValue) {
                    $placeholders[] = '?';
                    $this->bindings[] = $inValue;
                }
                return '(' . implode(', ', $placeholders) . ')';

            case 'BETWEEN':
            case 'NOT BETWEEN':
                $this->bindings[] = $value[0];
                $this->bindings[] = $value[1];
                return '? AND ?';

//            case 'LIKE':
//            case 'ILIKE':
//            case 'NOT LIKE':
//                $this->bindings[] = $value;
//                return '?';

            default:
                $this->bindings[] = $value;
                return '?';
        }
    }
}
