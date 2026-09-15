<?php

namespace Heritage\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Heritage\Contracts\Cache\Repository
     */
    public function store($name = null);
}
