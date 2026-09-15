<?php

namespace Heritage\Routing\Events;

class RouteMatched
{
    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Routing\Route  $route  The route instance.
     * @param  \Heritage\Http\Request  $request  The request instance.
     */
    public function __construct(
        public $route,
        public $request,
    ) {
    }
}
