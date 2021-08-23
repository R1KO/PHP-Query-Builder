<?php

use PHPUnit\Framework\TestCase;
use R1KO\Database\Contracts\IConnection;
use R1KO\Database\Connection;
use R1KO\Database\ConnectionWithBuilder;
use R1KO\Database\ConnectionFactory;
use R1KO\Database\ConnectionWithBuilderFactory;

class FactoryConnectionTest extends TestCase
{
    private function getDefaultParams(): array
    {
        $params = [
            'driver' => 'sqlite',
            'path'   => ':memory:',
        ];

        return $params;
    }

    public function testCreateByFactory(): void
    {
        $db = ConnectionFactory::create($this->getDefaultParams());

        $this->assertInstanceOf(IConnection::class, $db);
        $this->assertInstanceOf(Connection::class, $db);
    }

    public function testCreateWithBuilderByFactory(): void
    {
        $db = ConnectionWithBuilderFactory::create($this->getDefaultParams());

        $this->assertInstanceOf(IConnection::class, $db);
        $this->assertInstanceOf(ConnectionWithBuilder::class, $db);
    }
}
