<?php

namespace R1KO\QueryBuilder\Expressions;

use R1KO\Database\Contracts\IConnection;

abstract class AbstractConnectionExpression extends AbstractExpression
{
    private IConnection $db;

    public function __construct(string $expression, IConnection $db)
    {
        $this->db = $db;
    }

    protected function getConnection(): IConnection
    {
        return $this->db;
    }
}
