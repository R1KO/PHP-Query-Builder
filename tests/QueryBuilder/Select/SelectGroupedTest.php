<?php

namespace Tests\QueryBuilder;

use Tests\TestCase;
use Tests\Traits\UsersTable;

class SelectGroupedTest extends TestCase
{
    use UsersTable;

    public function testSelectWithGroupBy(): void
    {
        $this->createUsersTable();
        $users = $this->createUsers(5);
        $this->createUserByValues($users[0]);
        $this->createUserByValues($users[1]);

        $results = $this->db->table('users')
            ->select(['address'])
            ->groupBy(['address'])
            ->getCol('address');

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);

        $results = $this->db->table('users')
            ->select(['address', 'name'])
            ->groupBy(['address', 'name'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);
    }

    public function testSelectWithGroupByMultiplyColumns(): void
    {
        $this->createUsersTable();
        $users = $this->createUsers(5);
        $this->createUserByValues($users[0]);
        $this->createUserByValues($users[1]);

        $results = $this->db->table('users')
            ->select(['address', 'name'])
            ->groupBy(['address', 'name'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);
    }

    public function testSelectWithAddGroupBy(): void
    {
        $this->createUsersTable();
        $users = $this->createUsers(5);
        $this->createUserByValues($users[0]);
        $this->createUserByValues($users[1]);

        $results = $this->db->table('users')
            ->select(['address', 'name'])
            ->groupBy(['address'])
            ->addGroupBy(['name'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);
    }
}
