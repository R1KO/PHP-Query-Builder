<?php

namespace R1KO\QueryBuilder\Contracts;

use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Expressions\Raw;

interface IQueryBuilder
{
    public function getSql(): ?string;
    public function getBindings(): ?array;
    public static function asRaw(string $expression): Raw;
    public function raw(string $expression): Raw;
}
