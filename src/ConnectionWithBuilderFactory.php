<?php

namespace R1KO\QueryBuilder;

use R1KO\Database\Contracts\IConnection;
use R1KO\Database\Contracts\IDriver;
use R1KO\Database\ConnectionFactory;
use R1KO\QueryBuilder\ConnectionWithBuilder;
use PDO;

class ConnectionWithBuilderFactory extends ConnectionFactory
{
    public static function factoryConnectionInstance(PDO $pdo, IDriver $driver, array $params): IConnection
    {
        $connection = new ConnectionWithBuilder($pdo, $driver, $params);

        return $connection;
    }
}
