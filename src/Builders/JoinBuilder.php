<?php

namespace R1KO\QueryBuilder\Builders;

use R1KO\QueryBuilder\ConditionsBuilder;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\Exceptions\BuilderException;
use R1KO\QueryBuilder\Expressions\Column;
use R1KO\QueryBuilder\Expressions\Table;

class JoinBuilder
{
    private array $joins = [];
    private array $bindings = [];
    private IQueryBuilder $builder;

    final public function __construct(IQueryBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param string $type
     * @param Table $table
     * @param array|callable|null $conditions
     * @return void
     */
    public function addJoin(string $type, Table $table, $conditions = null): void
    {
        $this->joins[] = [
            'type'       => $type,
            'table'      => $table,
            'conditions' => $conditions,
        ];
    }

    /**
     * @param array $bindings
     * @return string
     * @throws BuilderException
     */
    public function getSql(array &$bindings = []): string
    {
        $this->bindings = &$bindings;
        $joins = [];

        foreach ($this->joins as $join) {
            $joins[] = $this->getClauseSql(strtoupper($join['type']), $join['table']->get(), $join['conditions']);
        }

        return implode(' ', $joins);
    }

    private function getClauseSql(string $type, string $table, $conditions): string
    {
        if (!$conditions) {
            return sprintf('%s JOIN %s', $type, $table);
        }

        if (is_array($conditions)) {
            $conditionBuilder = new ConditionsBuilder($this->builder);
            foreach ($conditions as $firstColumn => $secondColumn) {
                $conditionBuilder->and($firstColumn, new Column($secondColumn, $this->builder->getConnection()));
            }
            $conditionsSql = $conditionBuilder->getSql($this->bindings);
        } elseif (is_callable($conditions)) {
            $builderClass = get_class($this->builder);
            $builder = new $builderClass($this->builder->getConnection());
            $conditions($builder);
            $conditionsSql = $builder->getConditionsSql($this->bindings);
        } else {
            throw new BuilderException('Incorrect type of argument');
        }

        return sprintf(
            '%s JOIN %s ON (%s)',
            $type,
            $table,
            $conditionsSql
        );
    }
}
