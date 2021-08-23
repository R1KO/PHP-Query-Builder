<?php

namespace R1KO\QueryBuilder\Expressions;

class Condition
{
    private $condition;

    public function __construct(string $condition)
    {
        $this->condition = $condition;
    }

    public function __toString(): string
    {
        return $this->condition;
    }
}
