<?php

namespace Heritage\Queue\Connectors;

use Heritage\Queue\BackgroundQueue;

class BackgroundConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @return \Heritage\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new BackgroundQueue($config['after_commit'] ?? null);
    }
}
