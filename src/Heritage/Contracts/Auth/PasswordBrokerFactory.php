<?php

namespace Heritage\Contracts\Auth;

interface PasswordBrokerFactory
{
    /**
     * Get a password broker instance by name.
     *
     * @param  string|null  $name
     * @return \Heritage\Contracts\Auth\PasswordBroker
     */
    public function broker($name = null);
}
