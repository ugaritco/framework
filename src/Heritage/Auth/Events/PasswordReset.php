<?php

namespace Heritage\Auth\Events;

use Heritage\Queue\SerializesModels;

class PasswordReset
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Contracts\Auth\Authenticatable  $user  The user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
