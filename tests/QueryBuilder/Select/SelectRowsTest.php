<?php

namespace Tests\QueryBuilder;

use Tests\TestCase;
use Tests\Traits\UsersTable;

class SelectRowsTest extends TestCase
{
    use UsersTable;

    public function testSelectAllRows(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectOneRow(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $result = $this->db->table('users')
            ->getRow();

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testSelectOneColumn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $column = 'email';

        $results = $this->db->table('users')
            ->getCol($column);

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $this->assertIsString($results[0]);
    }

    public function testSelectOneColumnIterable(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $column = 'email';

        $results = $this->db->table('users')
            ->getColIterable($column);

        $this->assertNotNull($results);

        $rows = [];
        foreach ($results as $result) {
            $this->assertIsString($result);
            $rows[] = $result;
        }

        $this->assertCount(5, $rows);
    }

    public function testSelectOneColumnThroughSelect(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $column = 'email';

        $results = $this->db->table('users')
            ->select([$column])
            ->getCol();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $this->assertIsString($results[0]);
    }

    public function testSelectOneValue(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $result = $this->db->table('users')
            ->select(['name'])
            ->getOne();

        $this->assertNotNull($result);
        $this->assertIsString($result);
    }

    public function testSelectOneValueByColumn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $result = $this->db->table('users')
            ->getOne('name');

        $this->assertNotNull($result);
        $this->assertIsString($result);
    }

    public function testSelectAssocBySpecifyColumn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAssoc('id');

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectAssocByDefaultColumn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAssoc();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectIterable(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->getIterable();

        $this->assertNotNull($results);

        $rows = [];
        foreach ($results as $result) {
            $rows[] = $result;
        }

        $this->assertCount(5, $rows);
    }

    public function testSelectIterableByColumn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->getIterable('id');

        $this->assertNotNull($results);

        $rows = [];
        foreach ($results as $id => $result) {
            $this->assertEquals($result['id'], $id);
            $rows[] = $result;
        }

        $this->assertCount(5, $rows);
    }
}
