<?php

namespace Tests;

use Tests\Traits\UsersTable;

class MemoryLeakTest extends TestCase
{
    use UsersTable;

    public function testGarbageCollectorTest(): void
    {
        $this->createUsersTable();

        $memBeforeQuery = memory_get_usage(true);
        $query = $this->db->table('users')
            ->select(['*'])
            ->where('id <', 5)
            ->orWhere('id >', 10);

        $memBeforeExecQuery = memory_get_usage(true);

        $query->getAll();

        $this->assertEquals($memBeforeExecQuery, memory_get_usage(true));
        unset($query);
        $this->assertEquals($memBeforeQuery, memory_get_usage(true));
    }
}
