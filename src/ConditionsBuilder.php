<?php

namespace R1KO\QueryBuilder;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\Expressions\Raw;
use Closure;

class ConditionsBuilder
{
    public const SQL_AND = ' AND ';
    public const SQL_OR = ' OR ';

    /**
     * @var array
     */
    private $conditions = [];

    /**
     * @var array
     */
    private $bindings = [];
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
        if ($count == 1) {
            // closure
            [$condition] = $condition;

            if ($condition instanceof Closure) {
                $query = new static($this->builder);
                $condition($query);

                return '(' . $query->getConditionsSql($this->bindings) . ')';
            }

            // array conditions
            if (is_array($condition)) {
                $conditions = [];
                foreach ($condition as $column => $value) {
                    $conditions[] = $this->getSubConditionsSql([$column, $value]);
                }

                return implode(static::SQL_AND, $conditions);
            }

            // exception
            return '';
        }

        if ($count == 2) {
            // equals ( = )
            [$column, $value] = $condition;
            $operator = '=';
            if (strpos($column, ' ') !== false) {
                [$column, $operator] = explode(' ', $column);
            }
            $column = trim($column);

            return $this->getConditionSql($column, $operator, $value);
        }

        if ($count == 3) {
            [$column, $operator, $value] = $condition;
            $column = $this->getPreparedColumn($column);
            return $this->getConditionSql($column, $operator, $value);
        }

        return '';
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

        return $this->builder->quoteColumn(trim($column));
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @return string
     */
    private function getConditionSql(string $column, string $operator, $value): string
    {
        $operator = strtoupper(trim($operator));
        $value = $this->getValueByOperator($operator, $value);
        return implode(' ', [$column, $operator, $value]);
    }

    /**
     * @param string $operator
     * @param mixed  $value
     * @return string
     */
    private function getValueByOperator(string $operator, $value): string
    {
        switch ($operator) {
            /*
             // Default case (maybe set $value = NULL)
            case 'IS':
            case 'IS NOT':
                $conditions = [];
                foreach ($value as $index => $inValue) {
                    $placeholders[] = '?';
                    $this->bindings[] = $inValue;
                }
                return $operator . 'NULL';
            */
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

            case 'LIKE':
            case 'ILIKE':
            case 'NOT LIKE':
                $this->bindings[] = $value;
                return '(?)';

            default:
//                if (is_null($value)) {
//                    $value = 'NULL';
//                }
//                $this->bindValue($column, $value);
                $this->bindings[] = $value;
                return '?';
        }
    }
}
