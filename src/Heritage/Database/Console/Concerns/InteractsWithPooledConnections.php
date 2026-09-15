<?php

namespace Heritage\Database\Console\Concerns;

use Heritage\Support\Str;

trait InteractsWithPooledConnections
{
    /**
     * Resolve a database connection, preferring the direct variant when configured.
     *
     * @param  \Heritage\Database\ConnectionResolverInterface  $connections
     * @param  string|null  $database
     * @return \Heritage\Database\Connection
     */
    protected function resolveDirectConnectionIfPossible($connections, $database)
    {
        $name = $database ?: $connections->getDefaultConnection();

        $connection = $connections->connection($name);

        return $connection->hasDirectConnection() && ! Str::endsWith($name, ['::read', '::write', '::direct'])
            ? $connections->connection($name.'::direct')
            : $connection;
    }
}
