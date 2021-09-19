<?php

namespace Tests\QueryBuilder\Select;

use R1KO\QueryBuilder\Exceptions\BuilderException;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use stdClass;
use Tests\TestCase;
use Tests\Traits\UsersTable;
use TypeError;

class SelectFromTest extends TestCase
{
    use UsersTable;

    public function testSelectFromTable(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectFromTableWithAlias(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users', 'clients')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectFromDynamicTable(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->builder()
            ->fromSub(function (IQueryBuilder $query) {
                $query->table('users')
                    ->select(['name', 'address'])
                    ->limit(3)
                    ->getAll();
            }, 'clients')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(3, $results);
    }

    public function testSelectWithoutTable(): void
    {
        $this->expectException(BuilderException::class);

        $results = $this->db->builder()
            ->select(['*'])
            ->getAll();
    }

    public function testSelectWithInvalidTable(): void
    {
        $this->expectException(TypeError::class);

        $results = $this->db->builder()
            ->fromSub(new stdClass(), 'table')
            ->select(['*'])
            ->getAll();
    }
}
