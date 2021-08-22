<?php

namespace R1KO\QueryBuilder\Contracts;

use R1KO\Database\Contracts\IConnection;

interface IQueryBuilder
{
    public function __construct(IConnection $db);
}
