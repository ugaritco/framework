<?php

namespace Heritage\Auth\Events;

use Heritage\Http\Request;

class Lockout
{
    /**
     * The throttled request.
     *
     * @var \Heritage\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Http\Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
