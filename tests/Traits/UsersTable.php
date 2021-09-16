<?php

namespace Tests\Traits;

use R1KO\Database\Contracts\IConnection;

trait UsersTable
{
    abstract protected function getConnection(): IConnection;
    abstract protected function getFaker();
    abstract protected function getDriverName(): string;

    protected function getCreateUsersTableSqlite(): string
    {
        return <<<SQL
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    address TEXT
);
SQL;
    }

    protected function getCreateUsersTableMysql(): string
    {
        return <<<SQL
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    email VARCHAR(64) NOT NULL,
    address VARCHAR(256) NULL
);
SQL;
    }

    protected function createUsersTable(): void
    {
        $sql = <<<SQL
DROP TABLE IF EXISTS users;
SQL;
        $this->getConnection()->query($sql);

        $methodName = sprintf(
            'getCreateUsersTable%s',
            ucfirst(strtolower($this->getDriverName()))
        );
        $sql = $this->$methodName();
        $this->getConnection()->query($sql);
    }

    protected function createUsers(int $count = 1): array
    {
        $db = $this->getConnection();
        $faker = $this->getFaker();

        $users = [];
        foreach (range(1, $count) as $i) {
            $user = [
                'name'    => $faker->name(),
                'email'   => $faker->email(),
                'address' => $faker->address(),
            ];
            $name = $db->quote($user['name']);
            $email = $db->quote($user['email']);
            $address = $db->quote($user['address']);

            $sql = <<<SQL
INSERT INTO users (name, email, address) VALUES ({$name}, {$email}, {$address});
SQL;

            $db->query($sql);

            $users[] = $user;
        }

        return $users;
    }

    protected function createUserByValues(array $values): void
    {
        $db = $this->getConnection();

        $name = $db->quote($values['name']);
        $email = $db->quote($values['email']);
        $address = isset($values['address']) ? $db->quote($values['address']) : 'NULL';

        $sql = <<<SQL
INSERT INTO users (name, email, address) VALUES ({$name}, {$email}, {$address});
SQL;

        $db->query($sql);
    }
}
