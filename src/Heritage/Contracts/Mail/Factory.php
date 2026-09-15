<?php

namespace Heritage\Contracts\Mail;

interface Factory
{
    /**
     * Get a mailer instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Heritage\Contracts\Mail\Mailer
     */
    public function mailer($name = null);
}
