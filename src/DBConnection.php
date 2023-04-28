<?php

namespace AnzeBlaBla\Framework;


class DBConnection
{
    private \PDO $connection;

    /**
     * DBConnection constructor.
     * @param string $dsn
     * @param string $username
     * @param string $password
     */
    public function __construct($dsn, $username, $password)
    {
        $this->connection = new \PDO($dsn, $username, $password);
    }

    /**
     * @param string $query
     * @param array $args
     * @return array
     */
    public function query($query, $args)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($args);
        return $statement->fetchAll();
    }

    /**
     * @param string $query
     * @param array $args
     * @return mixed
     */
    public function queryOne($query, $args)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($args);
        return $statement->fetch();
    }

    /**
     * @param string $query
     * @param array $args
     */
    public function execute($query, $args)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($args);
    }

    /**
     * @return string
     */
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