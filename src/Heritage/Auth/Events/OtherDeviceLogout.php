<?php

namespace Heritage\Auth\Events;

use Heritage\Queue\SerializesModels;

class OtherDeviceLogout
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard  The authentication guard name.
     * @param  \Heritage\Contracts\Auth\Authenticatable  $user  \Heritage\Contracts\Auth\Authenticatable
     */
    public function __construct(
        public $guard,
        public $user,
    ) {
    }
}
