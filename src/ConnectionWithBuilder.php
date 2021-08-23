<?php

namespace R1KO\QueryBuilder;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\Database\Connection;
use R1KO\QueryBuilder\QueryBuilder;
use R1KO\QueryBuilder\Expressions\Raw;

class ConnectionWithBuilder extends Connection
{
    public function builder(): IQueryBuilder
    {
        return new QueryBuilder($this);
    }

    public function table(string $table, string $alias = null): IQueryBuilder
    {
        return $this->builder()->table($table, $alias);
    }

    public function raw(string $expression): Raw
    {
        return QueryBuilder::asRaw($expression);
    }
}
