<?php

namespace Heritage\Http\Client\Events;

use Heritage\Http\Client\Request;
use Heritage\Http\Client\Response;

class ResponseReceived
{
    /**
     * The request instance.
     *
     * @var \Heritage\Http\Client\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \Heritage\Http\Client\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Http\Client\Request  $request
     * @param  \Heritage\Http\Client\Response  $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
