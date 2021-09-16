<?php

namespace Tests\QueryBuilder\Insert;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\Exceptions\BuilderException;
use Tests\TestCase;
use Tests\Traits\PostsTable;
use Tests\Traits\UsersTable;

class InsertTest extends TestCase
{
    use UsersTable;
    use PostsTable;

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

    public function testInsertValuesWithGetId(): void
    {
        $this->createUsersTable();

        $values = [
            'name'    => 'test-name',
            'email'   => 'test-email',
            'address' => 'test-address',
        ];
        $id = $this->db->table('users')
            ->insertGetId($values);

        $this->assertNotNull($id);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);

        $row = array_shift($results);
        $this->assertEquals($id, $row['id']);
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

    public function testInsertBatchEmptyValues(): void
    {
        $this->expectException(BuilderException::class);

        $values = [];
        $result = $this->db->table('users')
            ->insertBatch($values);
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


    public function testInsertMassEmptyValues(): void
    {
        $this->expectException(BuilderException::class);

        $values = [];
        $this->db->table('users')
            ->insertMass($values);
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

        $faker = $this->getFaker();

        $address = $faker->address();
        $values = [
            'name'    => $faker->name(),
            'email'   => $faker->email(),
            'address' => $address,
        ];
        $this->createUserByValues($values);

        $columns = ['name', 'email', 'address'];

        $this->db->table('users')
            ->insertFrom(
                $columns,
                function (IQueryBuilder $query) use ($columns, $address) {
                    $query->select($columns)
                        ->from('users')
                        ->where('address !=', $address);
                }
            );

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(11, $results);
    }

    public function testInsertWithSubqueries(): void
    {
        $this->createUsersTable();
        $this->createUsers(1);
        $this->createPostsTable();
        $this->createPosts(5);

        $values = [
            'name'    => 'test',
            'email'   => function (IQueryBuilder $query) {
                $query->select(['topic'])
                    ->from('posts')
                    ->limit(1);
            },
            'address' => function (IQueryBuilder $query) {
                $query->select(['topic'])
                    ->from('posts')
                    ->limit(1);
            },
        ];
        $this->db->table('users')
            ->insertWithSub($values);

        $results = $this->db->table('users')
            ->select(['*'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(2, $results);
    }
}
