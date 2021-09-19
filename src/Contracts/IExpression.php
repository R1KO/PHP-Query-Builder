<?php

namespace R1KO\QueryBuilder\Contracts;

interface IExpression
{
    public function get(): string;
    public function __toString(): string;
}
