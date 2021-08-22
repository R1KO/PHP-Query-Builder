<?php

namespace Tests\QueryBuilder;

use Tests\QueryBuilder\TestCase;
use R1KO\Database\Contracts\IConnection;
use R1KO\Database\Contracts\IQueryBuilder;

class ConnectionTest extends TestCase
{
    public function testCreateByConnection(): void
    {
        $builder = $this->db->builder();

        $this->assertInstanceOf(IQueryBuilder::class, $builder);
    }

    public function testCreateByConnectionWithTable(): void
    {
        $builder = $this->db->table('users');

        $this->assertInstanceOf(IQueryBuilder::class, $builder);
    }
}
