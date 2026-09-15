<?php

namespace Heritage\Queue\Connectors;

use Heritage\Queue\DeferredQueue;

class DeferredConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @return \Heritage\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new DeferredQueue($config['after_commit'] ?? null);
    }
}
