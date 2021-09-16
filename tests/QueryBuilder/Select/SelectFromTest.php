<?php

namespace Tests\QueryBuilder\Select;

use R1KO\QueryBuilder\Exceptions\BuilderException;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use Tests\TestCase;
use Tests\Traits\UsersTable;

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
            ->from(function (IQueryBuilder $query) {
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

    public function testSelectFromDynamicTableWithoutAlias(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $this->expectException(BuilderException::class);

        $results = $this->db->builder()
            ->from(function (IQueryBuilder $query) {
                $query->table('users')
                    ->select(['name', 'address'])
                    ->limit(3);
            })
            ->select(['*'])
            ->getAll();
    }
}
