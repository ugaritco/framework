<?php

namespace Heritage\Routing\Events;

class Routing
{
    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Http\Request  $request  The request instance.
     */
    public function __construct(
        public $request,
    ) {
    }
}
