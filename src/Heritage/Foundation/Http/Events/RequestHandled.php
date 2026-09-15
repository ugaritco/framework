<?php

namespace Heritage\Foundation\Http\Events;

class RequestHandled
{
    /**
     * The request instance.
     *
     * @var \Heritage\Http\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \Heritage\Http\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  \Heritage\Http\Response  $response
     */
    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
