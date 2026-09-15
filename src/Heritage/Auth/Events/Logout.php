<?php

namespace Heritage\Auth\Events;

use Heritage\Queue\SerializesModels;

class Logout
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \Heritage\Contracts\Auth\Authenticatable  $user  The authenticated user.
     */
    public function __construct(
        public $guard,
        public $user,
    ) {
    }
}
