<?php

namespace R1KO\QueryBuilder;

use R1KO\QueryBuilder\Contracts\IExpression;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\Exceptions\ConditionException;
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
        $this->and(...$condition);

        return $this;
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function orWhere(...$condition)
    {
        $this->or(...$condition);

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
     * @return string
     */
    public function getSql(array &$bindings = []): string
    {
        $parts = [];
        $this->bindings = &$bindings;

        foreach ($this->conditions as $index => $conditionClause) {
            [$type, $condition] = $conditionClause;
            $sql = $this->getClauseSql($condition);

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
     * @throws ConditionException
     */
    private function getClauseSql(array $condition): string
    {
        $count = count($condition);
        if ($count === 0) {
            throw new ConditionException('Incorrect number of arguments');
        }

        if ($count === 1) {
            $condition = array_shift($condition);

            return $this->getConditionFromOneArgument($condition);
        }

        if ($count === 2) {
            [$column, $value] = $condition;

            return $this->getConditionFromTwoArguments($column, $value);
        }

        [$column, $operator, $value] = $condition;
        $column = $this->getPreparedColumn($column);
        return $this->getConditionSql($column, $operator, $value);
    }

    /**
     * @param array|Closure|IExpression $condition
     * @return string
     */
    private function getConditionFromOneArgument($condition): string
    {
        if ($condition instanceof Closure) {
            $query = new static($this->builder);
            $condition($query);

            return '(' . $query->getSql($this->bindings) . ')';
        }

        if (is_array($condition)) {
            $conditions = [];
            foreach ($condition as $column => $value) {
                $conditions[] = $this->getClauseSql([$column, $value]);
            }

            return implode(static::SQL_AND, $conditions);
        }

        if ($this->isExpression($condition)) {
            return sprintf('(%s)', $condition->get());
        }

        throw new ConditionException('Incorrect type of argument #1');
    }

    /**
     * @param string $column
     * @param $value
     * @return string
     */
    private function getConditionFromTwoArguments(string $column, $value): string
    {
        $column = trim($column);
        $preparedColumn = strtoupper(trim($column));

        if (in_array($preparedColumn, ['EXISTS', 'NOT EXISTS'])) {
            if (is_callable($value)) {
                $value = $this->builder->getSubQuerySelectSql($value, $this->bindings, false);
            } elseif ($this->isExpression($value)) {
                $value = $value->get();
            } else {
                throw new ConditionException('Value for "EXISTS" must be Callable or IExpression');
            }

            return sprintf('%s (%s)', $preparedColumn, $value);
        }

        $operator = '=';
        if (strpos($column, ' ') !== false) {
            [$column, $operator] = explode(' ', $column, 2);
        }

        $column = $this->quoteColumn(trim($column));

        if (is_callable($value)) {
            $value = $this->builder->getSubQuerySelectSql($value, $this->bindings);

            $operator = $this->getPreparedOperator($operator);
            return implode(' ', [$column, $operator, $value]);
        }

        return $this->getConditionSql($column, $operator, $value);
    }

    /**
     * @param string|IExpression $value
     * @return bool
     */
    private function isExpression($value): bool
    {
        return $value instanceof IExpression;
    }

    private function getPreparedColumn($column): string
    {
        if ($this->isExpression($column)) {
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
        if ($this->isExpression($value)) {
            $value = $value->get();
        } else {
            $value = $this->getValueByOperator($operator, $value);
        }

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
                foreach ($value as $inValue) {
                    $placeholders[] = '?';
                    $this->bindings[] = $inValue;
                }
                return '(' . implode(', ', $placeholders) . ')';

            case 'BETWEEN':
            case 'NOT BETWEEN':
                $this->bindings[] = $value[0];
                $this->bindings[] = $value[1];
                return '? AND ?';

// XXX: Equals with default case
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
