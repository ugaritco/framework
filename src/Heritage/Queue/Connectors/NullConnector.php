<?php

namespace Heritage\Queue\Connectors;

use Heritage\Queue\NullQueue;

class NullConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Heritage\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new NullQueue;
    }
}
