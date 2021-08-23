<?php

namespace Tests\QueryBuilder\Insert;

use Tests\TestCase;
use Tests\Traits\UsersTable;

class UpdateTest extends TestCase
{
    use UsersTable;

    public function testUpdateRow(): void
    {
        $this->createUsersTable();

        $this->createRows(3);

        $newEmail = 'test 66';
        $values = [
            'email' => $newEmail,
        ];
        $count = $this->db->table('users')
            ->where('name', 'test 1')
            ->update($values);

        $this->assertEquals(1, $count);

        $email = $this->db->table('users')
            ->select(['email'])
            ->where('name', 'test 1')
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
        $values = [];

        foreach (range(1, $count) as $i) {
            $values[] = [
                'name'    => 'test ' . $i,
                'email'   => 'test ' . $i,
                'address' => 'test ' . $i,
            ];
        }

        $result = $this->db->table('users')
            ->insertBatch($values);
    }

    public function testDeleteRowsCount(): void
    {
        $this->createUsersTable();

        $this->createUsersTable();

        $this->createRows(5);

        $this->assertEquals(5, $this->getCountRows());

        $count = $this->db->table('users')
            ->where('name', 'in', ['test 1', 'test 2'])
            ->delete();

        $this->assertEquals(2, $count);
        $this->assertEquals(3, $this->getCountRows());
    }
}
