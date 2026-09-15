<?php

namespace Heritage\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Heritage\Redis\Connections\Connection
     */
    public function connection($name = null);
}
