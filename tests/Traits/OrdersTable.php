<?php

namespace Tests\Traits;

use R1KO\Database\Contracts\IConnection;

trait OrdersTable
{
    abstract protected function getConnection(): IConnection;
    abstract protected function getFaker();
    abstract protected function getDriverName(): string;

    protected function getCreateOrdersTableSqlite(): string
    {
        return <<<SQL
CREATE TABLE orders (
    id INTEGER PRIMARY KEY,
    id_user INTEGER,
    id_product INTEGER,
    price INTEGER,
    comment TEXT DEFAULT NULL
);
SQL;
    }

    protected function getCreateOrdersTableMysql(): string
    {
        return <<<SQL
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    id_product INT,
    price INT,
    comment TEXT NOT NULL
);
SQL;
    }

    protected function createOrdersTable(): void
    {
        $sql = <<<SQL
DROP TABLE IF EXISTS orders;
SQL;
        $this->getConnection()->query($sql);

        $methodName = sprintf(
            'getCreateOrdersTable%s',
            ucfirst(strtolower($this->getDriverName()))
        );
        $sql = $this->$methodName();
        $this->getConnection()->query($sql);
    }

    protected function createOrders(int $count = 1): array
    {
        $db = $this->getConnection();
        $faker = $this->getFaker();

        $orders = [];
        foreach (range(1, $count) as $i) {
            $order = [
                'id_product' => $faker->numberBetween(),
                'price'      => $faker->numberBetween(20, 1000),
                'comment'    => $faker->realText(),
            ];

            $comment = $db->quote($order['comment']);

            $sql = <<<SQL
INSERT INTO orders (id_user, id_product, price, comment) 
VALUES (NULL, {$order['id_product']}, {$order['price']}, {$comment});
SQL;

            $db->query($sql);

            $orders[] = $order;
        }

        return $orders;
    }

    protected function createOrdersByUser(int $idUser, int $count = 1): array
    {
        $db = $this->getConnection();
        $faker = $this->getFaker();

        $orders = [];
        foreach (range(1, $count) as $i) {
            $order = [
                'id_user' => $idUser,
                'id_product' => $faker->numberBetween(),
                'price'      => $faker->numberBetween(20, 1000),
                'comment'    => $faker->realText(),
            ];

            $comment = $db->quote($order['comment']);

            $sql = <<<SQL
INSERT INTO orders (id_user, id_product, price, comment) 
VALUES ({$idUser}, {$order['id_product']}, {$order['price']}, {$comment});
SQL;

            $db->query($sql);

            $orders[] = $order;
        }

        return $orders;
    }
}
