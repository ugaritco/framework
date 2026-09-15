<?php

namespace Heritage\Database\Events;

use Heritage\Database\Connection;

class MigrationsPruned
{
    /**
     * The database connection instance.
     *
     * @var \Heritage\Database\Connection
     */
    public $connection;

    /**
     * The database connection name.
     *
     * @var string|null
     */
    public $connectionName;

    /**
     * The path to the directory where migrations were pruned.
     *
     * @var string
     */
    public $path;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Database\Connection  $connection
     * @param  string  $path
     */
    public function __construct(Connection $connection, string $path)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
        $this->path = $path;
    }
}
