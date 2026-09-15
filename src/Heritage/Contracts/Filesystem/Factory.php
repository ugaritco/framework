<?php

namespace Heritage\Contracts\Filesystem;

interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Heritage\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null);
}
