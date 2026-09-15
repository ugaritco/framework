<?php

namespace Heritage\Auth\Events;

use Heritage\Queue\SerializesModels;

class Login
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \Heritage\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  bool  $remember  Indicates if the user should be "remembered".
     */
    public function __construct(
        public $guard,
        public $user,
        public $remember,
    ) {
    }
}
