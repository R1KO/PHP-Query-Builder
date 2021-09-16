<?php

namespace Tests\QueryBuilder\Insert;

use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use Tests\TestCase;
use Tests\Traits\PostsTable;
use Tests\Traits\UsersTable;

class UpdateTest extends TestCase
{
    use UsersTable;
    use PostsTable;

    public function testUpdateRow(): void
    {
        $this->createUsersTable();

        $this->createRows(3);

        $res = $this->db->table('users')
            ->select(['*'])
            ->getall();

        $newEmail = 'test 66';
        $values = [
            'email' => $newEmail,
        ];
        $count = $this->db->table('users')
            ->where('name', 'test-name 1')
            ->update($values);

        $this->assertEquals(1, $count);

        $email = $this->db->table('users')
            ->select(['email'])
            ->where('name', 'test-name 1')
            ->getOne();

        $this->assertEquals($email, $newEmail);
    }

    public function testUpdateRowWithSubQuery(): void
    {
        $this->createUsersTable();
        $this->createRows(3);

        $this->createPostsTable();
        $this->createPosts(1);

        $newEmail = $this->db->table('posts')
            ->limit(1)
            ->getOne('topic');

        $values = [
            'email' => function (IQueryBuilder $query) {
                $query->select(['topic'])
                    ->from('posts')
                    ->limit(1);
            },
        ];
        $count = $this->db->table('users')
            ->where('name', 'test-name 1')
            ->updateWithSub($values);

        $this->assertEquals(1, $count);

        $email = $this->db->table('users')
            ->select(['email'])
            ->where('name', 'test-name 1')
            ->getOne();

        $this->assertEquals($email, $newEmail);
    }

    private function getCountRows(): int
    {
        return $this->db->table('users')
            ->count();
    }

    private function createRows(int $count): void
    {
        foreach (range(1, $count) as $i) {
            $values = [
                'name'    => 'test-name ' . $i,
                'email'   => 'test-email ' . $i,
                'address' => 'test-address ' . $i,
            ];

            $this->createUserByValues($values);
        }
    }
}
