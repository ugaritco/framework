<?php

namespace Heritage\Http\Client\Events;

use Heritage\Http\Client\ConnectionException;
use Heritage\Http\Client\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     *
     * @var \Heritage\Http\Client\Request
     */
    public $request;

    /**
     * The exception instance.
     *
     * @var \Heritage\Http\Client\ConnectionException
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Http\Client\Request  $request
     * @param  \Heritage\Http\Client\ConnectionException  $exception
     */
    public function __construct(Request $request, ConnectionException $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }
}
