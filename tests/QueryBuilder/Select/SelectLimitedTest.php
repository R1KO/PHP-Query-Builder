<?php

namespace Tests\QueryBuilder;

use Tests\TestCase;
use Tests\Traits\UsersTable;

class SelectLimitedTest extends TestCase
{
    use UsersTable;

    public function testSelectLimitRows(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->limit(2)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(2, $results);
    }

    public function testSelectLimitWithOffsetRows(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->limit(2)
            ->offset(2)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(2, $results);
    }
}
