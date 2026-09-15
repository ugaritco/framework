<?php

namespace Heritage\Queue\Connectors;

use Heritage\Queue\SyncQueue;

class SyncConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Heritage\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new SyncQueue($config['after_commit'] ?? null);
    }
}
