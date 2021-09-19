<?php

namespace R1KO\QueryBuilder\Expressions;

use R1KO\QueryBuilder\Contracts\IExpression;

class Raw implements IExpression
{
    private string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function get(): string
    {
        return $this->expression;
    }

    public function __toString(): string
    {
        return $this->get();
    }
}
