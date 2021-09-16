<?php

namespace Tests\QueryBuilder\Insert;

use Iterator;
use Tests\TestCase;
use Tests\Traits\UsersTable;
use R1KO\Database\Contracts\IConnection;

class InsertTest extends TestCase
{
    use UsersTable;

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

        $results = $this->db->table('users')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testInsertBatchValues(): void
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

        $results = $this->db->table('users')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($values), $results);
    }

    public function testInsertMassValues(): void
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
        $this->db->table('users')
            ->insertMass($values);

        $results = $this->db->table('users')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($values), $results);
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
        $this->db->table('users')
            ->insertMass($values, true);

        $results = $this->db->table('users')
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
                'test 1',
                'test 1',
                'test 1',
            ],
            [
                'name'    => 'test 2',
                'email'   => 'test 2',
                'address' => 'test 2',
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
                'test 3',
                'test 3',
                'test 3',
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

        $this->db->table('users')
            ->insertIterable($schema, $iterator(), true);

        $results = $this->db->table('users')
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
                'test 1',
                'test 1',
                'test 1',
            ],
            [
                'name'    => 'test 2',
                'email'   => 'test 2',
                'address' => 'test 2',
            ],
        ];

        $this->db->table('users')
            ->insertIterable($schema, $valuesSet);

        $results = $this->db->table('users')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(count($valuesSet), $results);
    }

}
