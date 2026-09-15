<?php

namespace Heritage\Contracts\Auth;

interface Factory
{
    /**
     * Get a guard instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Heritage\Contracts\Auth\Guard|\Heritage\Contracts\Auth\StatefulGuard
     */
    public function guard($name = null);

    /**
     * Set the default guard the factory should serve.
     *
     * @param  \UnitEnum|string|null  $name
     * @return void
     */
    public function shouldUse($name);
}
