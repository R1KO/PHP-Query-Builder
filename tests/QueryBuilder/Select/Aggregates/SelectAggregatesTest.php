<?php

namespace Tests\QueryBuilder\Aggregates;

use Tests\TestCase;
use Tests\Traits\UsersTable;
use Tests\Traits\PostsTable;
use Tests\Traits\OrdersTable;

class SelectAggregatesTest extends TestCase
{
    use UsersTable;
    use PostsTable;
    use OrdersTable;

    public function testSelectCount(): void
    {
        $this->createUsersTable();
        $this->createUsers(5);

        $countUsers = $this->db->table('users')
            ->count();

        $this->assertNotNull($countUsers);
        $this->assertEquals(5, $countUsers);
    }

    public function testSelectCountByColumn(): void
    {
        $this->createUsersTable();
        $this->createPostsTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->getColIterable('id');

        foreach ($results as $idUser) {
            $this->createPostsByUser($idUser, 5);
        }
        $this->createPosts(5);

        $countPosts = $this->db->table('posts')
            ->count('id_user');

        $this->assertNotNull($countPosts);
        $this->assertEquals(25, $countPosts);
    }

    public function testSelectCountByDistinctColumn(): void
    {
        $this->createUsersTable();

        $this->createUsers(5);
        $faker = $this->getFaker();

        $name = $faker->name();
        $values = [
            'name'    => $name,
            'email'   => $faker->email(),
            'address' => null
        ];
        $this->createUserByValues($values);
        $values = [
            'name'    => $name,
            'email'   => $faker->email(),
            'address' => null
        ];
        $this->createUserByValues($values);

        $countUniqueNames = $this->db->table('users')
            ->distinct()
            ->count('name');

        $this->assertNotNull($countUniqueNames);
        $this->assertEquals(6, $countUniqueNames);

        $countUniqueAddresses = $this->db->table('users')
            ->distinct()
            ->count('address');

        $this->assertNotNull($countUniqueAddresses);
        $this->assertEquals(5, $countUniqueAddresses);
    }

    public function testSelectSum(): void
    {
        $this->createOrdersTable();
        $this->createOrders(5);

        $results = $this->db->table('orders')
            ->getCol('price');

        $expectedSum = array_sum($results);

        $sum = $this->db->table('orders')
            ->sum('price');

        $this->assertNotNull($sum);
        $this->assertEquals($expectedSum, $sum);
    }

    public function testSelectSumDistinct(): void
    {
        $this->createOrdersTable();
        $orders = $this->createOrders(5);
        $this->createOrderByValues($orders[0]);

        $results = $this->db->table('orders')
            ->getCol('price');

        $expectedSum = array_sum(array_unique($results));

        $sum = $this->db->table('orders')
            ->distinct()
            ->sum('price');

        $this->assertNotNull($sum);
        $this->assertEquals($expectedSum, $sum);
    }

    public function testSelectSumRaw(): void
    {
        $this->createOrdersTable();
        $this->createOrders(5);

        $results = $this->db->table('orders')
            ->getCol('price');

        $expectedSum = array_sum(
            array_map(function ($price) {
                return $price * $price;
            }, $results)
        );

        $sum = $this->db->table('orders')
            ->sum($this->db->raw('price * price'));

        $this->assertNotNull($sum);
        $this->assertEquals($expectedSum, $sum);
    }

    public function testSelectAvg(): void
    {
        $this->createOrdersTable();
        $this->createOrders(5);

        $results = $this->db->table('orders')
            ->getCol('price');

        $expectedAvg = array_sum($results) / count($results);

        $avg = $this->db->table('orders')
            ->avg('price');

        $this->assertNotNull($avg);
        $this->assertEquals($expectedAvg, $avg);
    }

    public function testSelectMin(): void
    {
        $this->createOrdersTable();
        $this->createOrders(5);

        $results = $this->db->table('orders')
            ->getCol('price');

        $expectedValue = min($results);

        $min = $this->db->table('orders')
            ->min('price');

        $this->assertNotNull($min);
        $this->assertEquals($expectedValue, $min);
    }

    public function testSelectMax(): void
    {
        $this->createOrdersTable();
        $this->createOrders(5);

        $results = $this->db->table('orders')
            ->getCol('price');

        $expectedValue = max($results);

        $max = $this->db->table('orders')
            ->max('price');

        $this->assertNotNull($max);
        $this->assertEquals($expectedValue, $max);
    }
}
