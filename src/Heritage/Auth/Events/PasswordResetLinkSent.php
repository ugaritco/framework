<?php

namespace Heritage\Auth\Events;

use Heritage\Queue\SerializesModels;

class PasswordResetLinkSent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Contracts\Auth\CanResetPassword  $user  The user instance.
     */
    public function __construct(
        public $user,
    ) {
    }
}
