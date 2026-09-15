<?php

namespace Heritage\Auth\Events;

use Heritage\Queue\SerializesModels;

class Registered
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Contracts\Auth\Authenticatable  $user  The authenticated user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
