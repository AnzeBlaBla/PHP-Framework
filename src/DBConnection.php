<?php

namespace AnzeBlaBla\Framework;


class DBConnection
{
    private $connection;

    public function __construct($dsn, $username, $password)
    {
        $this->connection = new \PDO($dsn, $username, $password);
    }

    public function query($query, $args)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($args);
        return $statement->fetchAll();
    }

    public function queryOne($query, $args)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($args);
        return $statement->fetch();
    }

    public function execute($query, $args)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($args);
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function __destruct()
    {
        $this->connection = null;
    }

    public function __toString()
    {
        return 'DBConnection';
    }
}