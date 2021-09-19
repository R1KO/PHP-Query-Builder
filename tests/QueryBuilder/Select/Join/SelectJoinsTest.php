<?php

namespace Tests\QueryBuilder\Join;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use Tests\TestCase;
use Tests\Traits\UsersTable;
use Tests\Traits\PostsTable;

class SelectJoinsTest extends TestCase
{
    use UsersTable;
    use PostsTable;

    protected function doPreparePosts(int $countUsers, int $countPostsPerUser): void
    {
        $this->createUsersTable();
        $this->createPostsTable();
        $this->createUsers($countUsers);

        $results = $this->db->table('users')
            ->getColIterable('id');

        foreach ($results as $idUser) {
            $this->createPostsByUser($idUser, $countPostsPerUser);
        }
    }

    public function testInnerJoinByColumns(): void
    {
        $this->doPreparePosts(5, 5);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'author_name' => 'users.name'])
            ->join('users', ['posts.id_user' => 'users.id'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(25, $results);

        $firstRow = array_shift($results);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('id_user', $firstRow);
        $this->assertArrayHasKey('topic', $firstRow);
        $this->assertArrayHasKey('text', $firstRow);
        $this->assertArrayHasKey('author_name', $firstRow);
    }

    public function testInnerJoinWithTableAliasByColumns(): void
    {
        $this->doPreparePosts(5, 5);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'author_name' => 'authors.name'])
            ->join('users as authors', ['posts.id_user' => 'authors.id'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(25, $results);

        $firstRow = array_shift($results);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('id_user', $firstRow);
        $this->assertArrayHasKey('topic', $firstRow);
        $this->assertArrayHasKey('text', $firstRow);
        $this->assertArrayHasKey('author_name', $firstRow);
    }

    public function testLeftJoinByColumns(): void
    {
        $this->doPreparePosts(5, 5);
        $this->createPosts(2);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'author_name' => 'users.name'])
            ->leftJoin('users', ['posts.id_user' => 'users.id'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(27, $results);

        $firstRow = array_shift($results);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('id_user', $firstRow);
        $this->assertArrayHasKey('topic', $firstRow);
        $this->assertArrayHasKey('text', $firstRow);
        $this->assertArrayHasKey('author_name', $firstRow);
        $this->assertIsString($firstRow['author_name']);

        $lastRow = array_pop($results);
        $this->assertArrayHasKey('id', $lastRow);
        $this->assertArrayHasKey('id_user', $lastRow);
        $this->assertArrayHasKey('topic', $lastRow);
        $this->assertArrayHasKey('text', $lastRow);
        $this->assertArrayHasKey('author_name', $lastRow);
        $this->assertNull($lastRow['id_user']);
        $this->assertNull($lastRow['author_name']);
    }

    public function testRightJoinByColumns(): void
    {
        if ($this->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The SQLite don\'t support RIGHT JOIN.');
        }

        $this->doPreparePosts(5, 5);
        $this->createPosts(2);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'author_name' => 'users.name'])
            ->rightJoin('users', ['posts.id_user' => 'users.id'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(25, $results);

        $firstRow = array_shift($results);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('id_user', $firstRow);
        $this->assertArrayHasKey('topic', $firstRow);
        $this->assertArrayHasKey('text', $firstRow);
        $this->assertArrayHasKey('author_name', $firstRow);
        $this->assertIsString($firstRow['author_name']);
    }

    public function testFullJoinByColumns(): void
    {
        if (in_array($this->getDriverName(), ['sqlite', 'mysql'])) {
            $this->markTestSkipped('The MySQL, SQLite don\'t support FULL JOIN.');
        }

        $this->doPreparePosts(2, 2);
        $this->createUsers(2);
        $this->createPosts(2);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'author_name' => 'users.name'])
            ->fullJoin('users', ['posts.id_user' => 'users.id'])
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(8, $results);

        $firstRow = array_shift($results);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('id_user', $firstRow);
        $this->assertArrayHasKey('topic', $firstRow);
        $this->assertArrayHasKey('text', $firstRow);
        $this->assertArrayHasKey('author_name', $firstRow);
        $this->assertIsString($firstRow['author_name']);
    }

    public function testCrossJoinByColumns(): void
    {
        $this->doPreparePosts(2, 2);
        $this->createUsers(2);
        $this->createPosts(2);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'author_name' => 'users.name'])
            ->crossJoin('users')
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(24, $results);

        $firstRow = array_shift($results);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('id_user', $firstRow);
        $this->assertArrayHasKey('topic', $firstRow);
        $this->assertArrayHasKey('text', $firstRow);
        $this->assertArrayHasKey('author_name', $firstRow);
        $this->assertIsString($firstRow['author_name']);
    }

    public function testJoinByCallableConditions(): void
    {
        $this->doPreparePosts(2, 2);
        $this->createUsers(2);
        $this->createPosts(2);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'author_name' => 'users.name'])
            ->leftJoin('users', function (IQueryBuilder $query) {
                $query->whereColumn('posts.id_user', 'users.id')
                    ->orWhereRaw('users.id = posts.id_user');
            })
            ->getAll();

        $this->assertNotNull($results);
        $this->assertCount(6, $results);

        $firstRow = array_shift($results);
        $this->assertArrayHasKey('id', $firstRow);
        $this->assertArrayHasKey('id_user', $firstRow);
        $this->assertArrayHasKey('topic', $firstRow);
        $this->assertArrayHasKey('text', $firstRow);
        $this->assertArrayHasKey('author_name', $firstRow);
        $this->assertIsString($firstRow['author_name']);

        $lastRow = array_pop($results);
        $this->assertArrayHasKey('id', $lastRow);
        $this->assertArrayHasKey('id_user', $lastRow);
        $this->assertArrayHasKey('topic', $lastRow);
        $this->assertArrayHasKey('text', $lastRow);
        $this->assertArrayHasKey('author_name', $lastRow);
        $this->assertNull($lastRow['id_user']);
        $this->assertNull($lastRow['author_name']);
    }
}
