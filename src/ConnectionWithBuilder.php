<?php

namespace R1KO\QueryBuilder;

use R1KO\QueryBuilder\Contracts\IExpression;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\Database\Connection;
use R1KO\QueryBuilder\QueryBuilder;

class ConnectionWithBuilder extends Connection
{
    public function builder(): IQueryBuilder
    {
        return new QueryBuilder($this);
    }

    public function from(string $table, string $alias = null): IQueryBuilder
    {
        return $this->builder()->table($table, $alias);
    }

    public function table(string $table, string $alias = null): IQueryBuilder
    {
        return $this->from($table, $alias);
    }

    public function raw(string $expression): IExpression
    {
        return QueryBuilder::asRaw($expression);
    }
}
