<?php

namespace Tests\QueryBuilder\Conditions;

use Tests\TestCase;
use Tests\Traits\UsersTable;

class SelectConditionsTest extends TestCase
{
    use UsersTable;

    public function testSelectWithEquals(): void
    {
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);
        $user = [
            'name'    => $faker->name(),
            'email'   => $faker->email(),
            'address' => $faker->address(),
        ];
        $this->createUserByValues($user);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('name', $user['name'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testSelectWithMultiplyConditions(): void
    {
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);
        $user = [
            'name'    => $faker->name(),
            'email'   => $faker->email(),
            'address' => $faker->address(),
        ];
        $this->createUserByValues($user);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('name', $user['name'])
            ->where('email', $user['email'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testSelectWithArrayMultiplyConditions(): void
    {
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);
        $user = [
            'name'    => $faker->name(),
            'email'   => $faker->email(),
            'address' => $faker->address(),
        ];
        $this->createUserByValues($user);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where(
                [
                    'name'  => $user['name'],
                    'email' => $user['email']
                ]
            )
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testSelectWithOrConditions(): void
    {
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);
        $user = [
            'name'    => $faker->name(),
            'email'   => $faker->email(),
            'address' => $faker->address(),
        ];
        $this->createUserByValues($user);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('name', $user['name'])
            ->orWhere('email', $user['email'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testSelectWithComparisonConditions(): void
    {
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id >', 2)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(3, $results);
    }

    public function testSelectWithContainConditions(): void
    {
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);
        $users = [
            [
                'name'    => $faker->name(),
                'email'   => $faker->email(),
                'address' => $faker->address(),
            ],
            [
                'name'    => $faker->name(),
                'email'   => $faker->email(),
                'address' => $faker->address(),
            ],
            [
                'name'    => $faker->name(),
                'email'   => $faker->email(),
                'address' => $faker->address(),
            ],
        ];
        foreach ($users as $user) {
            $this->createUserByValues($user);
        }

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('email in', array_column($users, 'email'))
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(3, $results);
    }

    public function testSelectWithDuplicateColumnInConditions(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id >', 2)
            ->where('id <', 10)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(3, $results);
    }

    public function testSelectWithBetweenAndNotBetween(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id BETWEEN', [2, 10])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(4, $results);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id NOT BETWEEN', [2, 10])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testSelectWithInAndNotIn(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id IN', [1, 3])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(2, $results);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id NOT IN', [1, 3])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(3, $results);
    }

    public function testSelectWithLikeAndNotLike(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id LIKE', '%')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id NOT LIKE', '%')
            ->getAll();

        $this->assertNull($results);
    }

    public function testSelectWithIsNullAndIsNotNull(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);
        $values = [
            'name'    => 'test-name ' . time(),
            'email'   => 'test-email ' . time(),
        ];

        $this->createUserByValues($values);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('address IS', null)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('address IS NOT', null)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(5, $results);
    }

    public function testSelectWithSubCondition(): void
    {
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);
        $email = $faker->email();
        $users = [
            [
                'name'    => $faker->name(),
                'email'   => $email,
                'address' => $faker->address(),
            ],
            [
                'name'    => $faker->name(),
                'email'   => $email,
                'address' => $faker->address(),
            ],
            [
                'name'    => $faker->name(),
                'email'   => $faker->email(),
                'address' => $faker->address(),
            ],
        ];
        foreach ($users as $user) {
            $this->createUserByValues($user);
        }

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('email', $email)
            ->where(static function ($query) use ($users) {
                $addresses = array_column($users, 'address');
                $query->where('address', $addresses[1])
                    ->orWhere('address', $addresses[2]);
            })
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
    }

    public function testSelectWithSubQuery(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('address', static function ($query) {
                $query->select(['address'])
                    ->from('users')
                    ->orderBy('id', 'ASC')
                    ->limit(1);
            })
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(1, $results);

        $resultRow = array_shift($results);

        $row = $this->db->table('users')
            ->select(['email', 'address'])
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->getRow();

        $this->assertEquals($resultRow['email'], $row['email']);
        $this->assertEquals($resultRow['address'], $row['address']);
    }
}
