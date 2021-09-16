<?php

namespace Tests\QueryBuilder\Insert;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use Tests\TestCase;
use Tests\Traits\UsersTable;

class InsertTest extends TestCase
{
    use UsersTable;

    public function testInsertValues(): void
    {
        $this->createUsersTable();

        $values = [
            'name'    => 'test-name',
            'email'   => 'test-email',
            'address' => 'test-address',
        ];
        $this->db->table('users')
            ->insert($values);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testInsertBatchValues(): void
    {
        $this->createUsersTable();

        $values = [
            [
                'name'    => 'test-name 1',
                'email'   => 'test-email 1',
                'address' => 'test-address 1',
            ],
            [
                'test-name 2',
                'test-email 2',
                'test-address 2',
            ],
        ];
        $result = $this->db->table('users')
            ->insertBatch($values);

        $this->assertNotNull($result);
        $this->assertEquals(count($values), $result);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($values), $results);
    }

    public function testInsertMassValues(): void
    {
        $this->createUsersTable();

        $values = [
            [
                'name'    => 'test-name 1',
                'email'   => 'test-email 1',
                'address' => 'test-address 1',
            ],
            [
                'test-name 2',
                'test-email 2',
                'test-address 2',
            ],
        ];
        $this->db->table('users')
            ->insertMass($values);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($values), $results);
    }

    public function testInsertValuesMassWithTransaction(): void
    {
        $this->createUsersTable();

        $values = [
            [
                'name'    => 'test-name 1',
                'email'   => 'test-email 1',
                'address' => 'test-address 1',
            ],
            [
                'test-name 2',
                'test-email 2',
                'test-address 2',
            ],
        ];
        $this->db->table('users')
            ->insertMass($values, true);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($values), $results);
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
                'test-name 1',
                'test-email 1',
                'test-address 1',
            ],
            [
                'name'    => 'test-name 2',
                'email'   => 'test-email 2',
                'address' => 'test-address 2',
            ],
        ];

        $iterator = static function () use ($valuesSet): iterable {
            foreach ($valuesSet as $values) {
                yield $values;
            }
        };

        $this->db->table('users')
            ->insertIterable($schema, $iterator());

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
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
                'test-name 3',
                'test-email 3',
                'test-address 3',
            ],
            [
                'name'    => 'test-name 4',
                'email'   => 'test-email 4',
                'address' => 'test-address 4',
            ],
        ];

        $iterator = function () use ($valuesSet): iterable {
            foreach ($valuesSet as $values) {
                yield $values;
            }
        };

        $this->db->table('users')
            ->insertIterable($schema, $iterator(), true);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
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
                'test-name 1',
                'test-email 1',
                'test-address 1',
            ],
            [
                'name'    => 'test-name 2',
                'email'   => 'test-email 2',
                'address' => 'test-address 2',
            ],
        ];

        $this->db->table('users')
            ->insertIterable($schema, $valuesSet);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($valuesSet), $results);
    }

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
                }
            );

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(10, $results);
    }
/*
    public function testInsertWithSubqueries(): void
    {
        $this->createUsersTable();
        $this->createUsers(1);

        $values = [
            'name'    => 'test',
            'email'   => function (IQueryBuilder $query) {
                $query->select(['email'])
                    ->from('users')
                    ->limit(1);
            },
            'address' => function (IQueryBuilder $query) {
                $query->select(['address'])
                    ->from('users')
                    ->limit(1);
            },
        ];
        $this->db->table('users')
            ->insert($values);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(2, $results);
    }
*/
}
