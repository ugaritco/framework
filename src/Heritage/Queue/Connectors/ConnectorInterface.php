<?php

namespace Heritage\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Heritage\Contracts\Queue\Queue
     */
    public function connect(array $config);
}
