<?php

namespace Heritage\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Heritage\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
