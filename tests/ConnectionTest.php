<?php

namespace Tests;

use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\ConnectionWithBuilderFactory;
use R1KO\QueryBuilder\ConnectionWithBuilder;

class ConnectionTest extends TestCase
{
    public function testCreateConnectionWithBuilderByFactory(): void
    {
        $db = ConnectionWithBuilderFactory::create($this->getDefaultParams());

        $this->assertInstanceOf(IConnection::class, $db);
        $this->assertInstanceOf(ConnectionWithBuilder::class, $db);
    }

    public function testCreateBuilderByConnection(): void
    {
        $builder = $this->db->builder();

        $this->assertInstanceOf(IQueryBuilder::class, $builder);
    }

    public function testCreateBuilderByConnectionWithTable(): void
    {
        $builder = $this->db->table('users');

        $this->assertInstanceOf(IQueryBuilder::class, $builder);
    }
}
