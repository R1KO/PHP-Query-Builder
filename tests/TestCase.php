<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use R1KO\Database\Contracts\IConnection;
use R1KO\QueryBuilder\Contracts\IQueryBuilder;
use R1KO\QueryBuilder\ConnectionWithBuilderFactory;

class TestCase extends BaseTestCase
{
    /**
     * @var IConnection
     */
    protected $db;

    protected function setUp(): void
    {
        $this->db = $this->createConnection();
    }

    protected function createConnection(): IConnection
    {
        return ConnectionWithBuilderFactory::create($this->getDefaultParams());
    }

    protected function getConnection(): IConnection
    {
        return $this->db;
    }

    protected function getDefaultParams(): array
    {
        return [
            'driver'   => $_ENV['DB_DRIVER'],
            'host'     => $_ENV['DB_HOST'],
            'port'     => $_ENV['DB_PORT'],
            'name'     => $_ENV['DB_NAME'],
            'user'     => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset'  => $_ENV['DB_CHARSET'],
            'path'     => $_ENV['DB_PATH'],
        ];
    }

    protected function getDriverName(): string
    {
        return $this->db->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    protected function getFaker()
    {
        return \Faker\Factory::create();
    }
}
