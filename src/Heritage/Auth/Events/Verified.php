<?php

namespace Heritage\Auth\Events;

use Heritage\Queue\SerializesModels;

class Verified
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Contracts\Auth\MustVerifyEmail  $user  The verified user.
     */
    public function __construct(
        public $user,
    ) {
    }
}
