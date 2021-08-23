<?php

namespace Tests\QueryBuilder;

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
        $faker = $this->getFaker();

        $this->createUsersTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id >', 2)
            ->where('id <', 10)
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(3, $results);

        $results = $this->db->table('users')
            ->select(['*'])
            ->where('id BETWEEN', [2, 10])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(4, $results);
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
}
