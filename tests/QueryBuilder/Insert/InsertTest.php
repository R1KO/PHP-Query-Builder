<?php

namespace Tests\QueryBuilder\Insert;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use Tests\TestCase;
use Tests\Traits\UsersTable;
use R1KO\Database\Contracts\IConnection;

class InsertTest extends TestCase
{
    use UsersTable;
/*
    public function testInsertValues(): void
    {
        $this->createUsersTable();

        $values = [
            'name'    => 'test',
            'email'   => 'test',
            'address' => 'test',
        ];
        $id = $this->db->table('users')
            ->insert($values);

        $this->assertNotNull($id);
    }

    public function testInsertValuesBatch(): void
    {
        $this->createUsersTable();

        $values = [
            [
                'name'    => 'test 1',
                'email'   => 'test 1',
                'address' => 'test 1',
            ],
            [
                'name'    => 'test 2',
                'email'   => 'test 2',
                'address' => 'test 2',
            ],
        ];
        $result = $this->db->table('users')
            ->insertBatch($values);

        $this->assertNotNull($result);
        $this->assertEquals(count($values), $result);
    }

    public function testInsertValuesMass(): void
    {
        $this->createUsersTable();

        $values = [
            [
                'name'    => 'test 1',
                'email'   => 'test 1',
                'address' => 'test 1',
            ],
            [
                'name'    => 'test 2',
                'email'   => 'test 2',
                'address' => 'test 2',
            ],
        ];
        $result = $this->db->table('users')
            ->insertMass($values);

        $this->assertNotNull($result);
        $this->assertCount(count($values), $result);
    }

    public function testInsertValuesMassWithTransaction(): void
    {
        $this->createUsersTable();

        $values = [
            [
                'name'    => 'test 1',
                'email'   => 'test 1',
                'address' => 'test 1',
            ],
            [
                'name'    => 'test 2',
                'email'   => 'test 2',
                'address' => 'test 2',
            ],
        ];
        $result = $this->db->table('users')
            ->insertMass($values, true);

        $this->assertNotNull($result);
        $this->assertCount(count($values), $result);
    }

    public function testInsertValuesIterable(): void
    {
        $this->createUsersTable();

        $schema = [
            'name',
            'email',
            'address',
        ];
        $valuesSet = [
            [
                'name'    => 'test 1',
                'email'   => 'test 1',
                'address' => 'test 1',
            ],
            [
                'name'    => 'test 2',
                'email'   => 'test 2',
                'address' => 'test 2',
            ],
        ];

        $iterator = function () use ($valuesSet): iterable {
            foreach ($valuesSet as $values) {
                yield $values;
            }
        };

        $result = $this->db->table('users')
            ->insertIterable($schema, $iterator());

        $this->assertNotNull($result);
        $this->assertInstanceOf(\Iterator::class, $result);

        $results = [];
        foreach ($result as $value) {
            $results[] = $value;
        }

        $this->assertCount(count($valuesSet), $results);
    }

    public function testInsertValuesIterableWithTransaction(): void
    {
        $this->createUsersTable();

        $schema = [
            'name',
            'email',
            'address',
        ];
        $valuesSet = [
            [
                'name'    => 'test 3',
                'email'   => 'test 3',
                'address' => 'test 3',
            ],
            [
                'name'    => 'test 4',
                'email'   => 'test 4',
                'address' => 'test 4',
            ],
        ];

        $iterator = function () use ($valuesSet): iterable {
            foreach ($valuesSet as $values) {
                yield $values;
            }
        };

        $result = $this->db->table('users')
            ->insertIterable($schema, $iterator(), true);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\Iterator::class, $result);

        $results = [];
        foreach ($result as $value) {
            $results[] = $value;
        }

        $this->assertCount(count($valuesSet), $results);
    }

    public function testInsertValuesIterableArrayCompatibility(): void
    {
        $this->createUsersTable();

        $schema = [
            'name',
            'email',
            'address',
        ];
        $valuesSet = [
            [
                'name'    => 'test 1',
                'email'   => 'test 1',
                'address' => 'test 1',
            ],
            [
                'name'    => 'test 2',
                'email'   => 'test 2',
                'address' => 'test 2',
            ],
        ];

        $result = $this->db->table('users')
            ->insertIterable($schema, $valuesSet);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\Iterator::class, $result);

        $results = [];
        foreach ($result as $value) {
            $results[] = $value;
        }

        $this->assertCount(count($valuesSet), $results);
    }
*/
    public function testInsertFromOtherTable(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $columns = ['name', 'email', 'address'];

        $this->db->table('users')
            ->insertFrom(
                $columns,
                function (IQueryBuilder $query) use ($columns) {
                    $query->select($columns)
                        ->from('users');
                });


        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(10, $results);
    }
}
