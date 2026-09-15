<?php

namespace Heritage\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  string|null  $name
     * @return \Heritage\Contracts\Broadcasting\Broadcaster
     */
    public function connection($name = null);
}
