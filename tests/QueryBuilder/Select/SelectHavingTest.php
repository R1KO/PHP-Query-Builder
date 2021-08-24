<?php

namespace Tests\QueryBuilder;

use Tests\TestCase;
use Tests\Traits\UsersTable;

class SelectHavingTest extends TestCase
{
    use UsersTable;

    public function testSelectByGrouping(): void
    {
        $this->createUsersTable();
        $users = $this->createUsers(5);
        $this->createUserByValues($users[0]);
        $this->createUserByValues($users[1]);
        $this->createUserByValues($users[1]);

        $results = $this->db->table('users')
            ->select([$this->db->raw('COUNT(address) as addresses')])
            ->groupBy('address')
            ->having($this->db->raw('COUNT(address)'), '>', 1)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(2, $results);
    }
}
