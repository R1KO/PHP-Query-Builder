<?php

namespace Tests\QueryBuilder;

use Tests\TestCase;
use Tests\Traits\UsersTable;

class SelectOrderedTest extends TestCase
{
    use UsersTable;

    public function testSelectWithOrderByAsc(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->orderBy('name', 'ASC')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectWithOrderByDesc(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->orderBy('name', 'DESC')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectWithOrderAsc(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->orderAsc('name')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectWithOrderDesc(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->orderDesc('name')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectWithOrderByCombine(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->orderBy('name', 'ASC')
            ->orderDesc('address')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }
}
