<?php

namespace R1KO\QueryBuilder\Expressions;

use R1KO\QueryBuilder\Contracts\IExpression;

abstract class AbstractExpression implements IExpression
{
    public function __toString(): string
    {
        return $this->get();
    }
}
