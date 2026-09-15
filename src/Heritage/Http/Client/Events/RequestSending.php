<?php

namespace Heritage\Http\Client\Events;

use Heritage\Http\Client\Request;

class RequestSending
{
    /**
     * The request instance.
     *
     * @var \Heritage\Http\Client\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Http\Client\Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
