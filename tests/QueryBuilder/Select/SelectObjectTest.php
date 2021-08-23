<?php

namespace Tests\QueryBuilder;

use Tests\TestCase;
use Tests\Traits\UsersTable;

/*
class ExampleClass
{

}
*/
class SelectObjectTest extends TestCase
{
    use UsersTable;

/*
    public function testSelectAsStdClass(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->as()
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $this->assertInstanceOf(\stdClass::class, array_shift($results));
    }

    public function testSelectAsExampleClass(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->as(ExampleClass::class)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
        $this->assertInstanceOf(ExampleClass::class, array_shift($results));
    }

    public function testSelectIntoObject(): void
    {
        $this->createUsersTable();
        $this->createUsers(1);

        $object = new ExampleClass();
        $object->value = 'test';

        $result = $this->db->table('users')
            ->as($object)
            ->getRow();

        $this->assertNotNull($result);
        $this->assertInstanceOf(ExampleClass::class, $result);
        $this->assertSame($object, $result);
        $this->assertEquals($object->value, $result->value);
    }

    public function testSelectAsFunction(): void
    {
        $this->createUsersTable();
        $this->createUsers(3);

        $object = new ExampleClass();
        $object->value = 'test';

        $results = $this->db->table('users')
            ->as(function ($values) {
                var_dump('as function', $values);
            })
            ->getAll();

        var_dump($results);

        $this->assertNotNull($results);
        $this->assertCount(3, $results);
        $this->assertInstanceOf(ExampleClass::class, array_shift($results));
    }
    */
    public function testSelectAsFunction(): void
    {
        $this->createUsersTable();
        $this->createUsers(3);

        $db = $this->db->getPDO();

        var_dump($db);
        $this->assertNotNull($db);
    }
/*
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

    public function testSelectByGrouping(): void
    {
        $this->createUsersTable();
        $users = $this->createUsers(5);
        $this->createUserByValues($users[0]);
        $this->createUserByValues($users[1]);

        $results = $this->db->table('users')
            ->select(['address'])
            ->groupBy('address')
            ->getCol('address');

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);

        $results = $this->db->table('users')
            ->select(['address'])
            ->groupBy(['address'])
            ->getCol('address');

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);

        $results = $this->db->table('users')
            ->select(['address', 'name'])
            ->groupBy('address', 'name')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);

        $results = $this->db->table('users')
            ->select(['address', 'name'])
            ->groupBy(['address', 'name'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($users), $results);
    }

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

    public function testSelectWithOrder(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->orderBy('name', 'ASC')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);

        $results = $this->db->table('users')
            ->orderBy('name', 'DESC')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);

        $results = $this->db->table('users')
            ->orderAsc('name')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);

        $results = $this->db->table('users')
            ->orderDesc('name')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);

        $results = $this->db->table('users')
            ->orderBy('name', 'ASC')
            ->orderDesc('address')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }
*/
}
