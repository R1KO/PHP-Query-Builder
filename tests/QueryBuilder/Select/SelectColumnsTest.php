<?php

namespace Tests\QueryBuilder;

use R1KO\Database\QueryBuilder;
use Tests\QueryBuilder\TestCase;
use Tests\Traits\UsersTable;
use R1KO\Database\Contracts\IConnection;

class SelectColumnsTest extends TestCase
{
    use UsersTable;

    public function testSelectAllColumns(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectDefaultColumns(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectSpecifyColumns(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = ['name', 'address'];

        $results = $this->db->table('users')
            ->select($columns)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $this->assertCount(count($columns), $results[0]);
    }

    public function testSelectWithColumnAliases(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = ['id', 'name' => 'user_name', 'address'];

        $result = $this->db->table('users')
            ->select($columns)
            ->getRow();

        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('user_name', $result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayNotHasKey('name', $result);
    }

    public function testSelectReplacedColumns(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = ['name', 'address'];

        $results = $this->db->table('users')
            ->select($columns)
            ->select(['id'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $firstRow = array_shift($results);
        $this->assertCount(1, $firstRow);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayNotHasKey('name', $firstRow);
        $this->assertArrayNotHasKey('address', $firstRow);
    }

    public function testSelectAppendColumns(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = ['name', 'address'];

        $results = $this->db->table('users')
            ->select($columns)
            ->addSelect(['id'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $firstRow = array_shift($results);
        $this->assertCount(count($columns) + 1, $firstRow);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('address', $firstRow);
    }

    public function testSelectRawColumn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = [
            'id',
            QueryBuilder::raw('address AS user_address'),
        ];
        $results = $this->db->table('users')
            ->select($columns)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $firstRow = array_shift($results);
        $this->assertCount(count($columns), $firstRow);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('user_address', $firstRow);
        $this->assertArrayNotHasKey('address', $firstRow);
    }

    public function testSelectDistinct(): void
    {
        $this->createUsersTable();

        $faker = $this->getFaker();

        $users = $this->createUsers(5);
        $firstUser = array_shift($users);

        $values = [
            'name'    => $firstUser['name'],
            'email'   => $faker->email(),
            'address' => null
        ];
        $this->createUserByValues($values);

        $results = $this->db->table('users')
            ->select(['name', 'email', 'address'])
            ->distinct()
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(6, $results);

        $results = $this->db->table('users')
            ->select(['name'])
            ->distinct()
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);

        $results = $this->db->table('users')
            ->distinct()
            ->getCol('name');

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }
}
