<?php

namespace R1KO\QueryBuilder\Expressions;

class Raw
{
    private $expression;

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
