<?php

namespace Tests\QueryBuilder;

use Tests\QueryBuilder\TestCase;
use Tests\Traits\UsersTable;
use Tests\Traits\PostsTable;
use R1KO\Database\Contracts\IConnection;

class SelectJoinsTest extends TestCase
{
    use UsersTable;
    use PostsTable;

    protected function doPreparePosts(): void
    {
        $this->createUsersTable();
        $this->createPostsTable();
        $this->createUsers(5);

        $results = $this->db->table('users')
            ->getColIterable('id');

        foreach ($results as $idUser) {
            $this->createPostsByUser($idUser, 5);
        }
    }

    public function testInnerJoinByColumns(): void
    {
        $this->doPreparePosts();

        $results = $this->db->table('posts')
            ->select(['posts.*', 'users.name' => 'author_name'])
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
        $this->doPreparePosts();

        $results = $this->db->table('posts')
            ->select(['posts.*', 'authors.name' => 'author_name'])
            ->join(['users' => 'authors'], ['posts.id_user' => 'authors.id'])
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
        $this->doPreparePosts();
        $this->createPosts(2);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'users.name' => 'author_name'])
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
            return;
        }

        $this->doPreparePosts();
        $this->createPosts(2);

        $results = $this->db->table('posts')
            ->select(['posts.*', 'users.name' => 'author_name'])
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
}
