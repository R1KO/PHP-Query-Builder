<?php

namespace Tests\QueryBuilder;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use Tests\TestCase;
use Tests\Traits\UsersTable;

class SelectColumnsTest extends TestCase
{
    use UsersTable;
/*
    public function testSelectAllColumns(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();


        $columns = ['id', 'name', 'address'];
        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $firstRow = array_shift($results);
        $this->assertCount(count($columns), $firstRow);
        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('address', $firstRow);
        $this->assertArrayHasKey('id', $firstRow);
    }

    public function testSelectColumnsByDefault(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->getAll();

        $columns = ['id', 'name', 'address'];
        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $firstRow = array_shift($results);
        $this->assertCount(count($columns), $firstRow);
        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('address', $firstRow);
        $this->assertArrayHasKey('id', $firstRow);
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
        $firstRow = array_shift($results);
        $this->assertCount(count($columns), $firstRow);
        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('address', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
    }

    public function testSelectWithColumnAliases(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = ['id', 'name' => 'user_name', 'address'];

        $results = $this->db->table('users')
            ->select($columns)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $firstRow = array_shift($results);
        $this->assertCount(count($columns), $firstRow);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('user_name', $firstRow);
        $this->assertArrayHasKey('address', $firstRow);
        $this->assertArrayNotHasKey('name', $firstRow);
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
            $this->db->raw('address AS user_address'),
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

    public function testSelectRawColumnWithAlias(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = [
            'id',
            'user_address' => $this->db->raw('LOWER(address)'),
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
*/

    public function testSelectSubqueryColumn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = [
            'id',
            function (IQueryBuilder $query) {
                $query->select(['address'])
                    ->from('users', 'sub_users')
                    ->where($this->db->raw('sub_users.id = users.id'))
                    ->limit(1)
                    ->getCol();
            },
        ];
        $results = $this->db->table('users')
            ->select($columns)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $firstRow = array_shift($results);
        $this->assertCount(count($columns), $firstRow);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayNotHasKey('user_address', $firstRow);
        $this->assertArrayNotHasKey('address', $firstRow);
    }

    public function testSelectSubqueryColumnWithAlias(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = [
            'id',
            'user_address' => function (IQueryBuilder $query) {
                $query->select(['address'])
                    ->from('users', 'sub_users')
                    ->where($this->db->raw('sub_users.id = users.id'))
                    ->limit(1)
                    ->getCol();
            },
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
}
