<?php

namespace R1KO\QueryBuilder\Expressions;

use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Contracts\IExpression;

class Column implements IExpression
{
    private string $column;
    private IConnection $db;

    public function __construct(string $column, IConnection $db)
    {
        $this->db = $db;
        $this->column = $column;
    }

    private function quote(string $name): string
    {
        return $this->db->getDriver()->quoteColumnName($name);
    }

    public function get(): string
    {
        return $this->quote($this->column);
    }

    public function __toString(): string
    {
        return $this->get();
    }
}
