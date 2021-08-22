<?php

namespace Tests\Traits;

use R1KO\Database\Contracts\IConnection;

trait PostsTable
{
    abstract protected function getConnection(): IConnection;
    abstract protected function getFaker();
    abstract protected function getDriverName(): string;

    protected function getCreatePostsTableSqlite(): string
    {
        return <<<SQL
CREATE TABLE posts (
    id INTEGER PRIMARY KEY,
    id_user INTEGER,
    topic TEXT NOT NULL,
    text TEXT NOT NULL
);
SQL;
    }

    protected function getCreatePostsTableMysql(): string
    {
        return <<<SQL
CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    topic VARCHAR(256) NOT NULL,
    text TEXT NOT NULL
);
SQL;
    }

    protected function createPostsTable(): void
    {
        $sql = <<<SQL
DROP TABLE IF EXISTS posts;
SQL;
        $this->getConnection()->query($sql);

        $methodName = sprintf(
            'getCreatePostsTable%s',
            ucfirst(strtolower($this->getDriverName()))
        );
        $sql = $this->$methodName();
        $this->getConnection()->query($sql);
    }

    protected function createPosts(int $count = 1): array
    {
        $db = $this->getConnection();
        $faker = $this->getFaker();

        $posts = [];
        foreach (range(1, $count) as $i) {
            $post = [
                'topic'   => $faker->catchPhrase(),
                'text'    => $faker->realText(),
            ];

            $topic = $db->quote($post['topic']);
            $text = $db->quote($post['text']);

            $sql = <<<SQL
INSERT INTO posts (id_user, topic, text) VALUES (NULL, {$topic}, {$text});
SQL;

            $db->query($sql);

            $posts[] = $post;
        }

        return $posts;
    }

    protected function createPostsByUser(int $idUser, int $count = 1): array
    {
        $db = $this->getConnection();
        $faker = $this->getFaker();

        $posts = [];
        foreach (range(1, $count) as $i) {
            $post = [
                'id_user' => $idUser,
                'topic'   => $faker->catchPhrase(),
                'text'    => $faker->realText(),
            ];

            $topic = $db->quote($post['topic']);
            $text = $db->quote($post['text']);

            $sql = <<<SQL
INSERT INTO posts (id_user, topic, text) VALUES ({$idUser}, {$topic}, {$text});
SQL;

            $db->query($sql);

            $posts[] = $post;
        }

        return $posts;
    }
}
