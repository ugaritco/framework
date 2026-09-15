<?php

namespace Heritage\Foundation\Routing;

use Heritage\Routing\CallableDispatcher;
use Heritage\Routing\Route;

class PrecognitionCallableDispatcher extends CallableDispatcher
{
    /**
     * Dispatch a request to a given callable.
     *
     * @param  \Heritage\Routing\Route  $route
     * @param  callable  $callable
     * @return mixed
     */
    public function dispatch(Route $route, $callable)
    {
        $this->resolveParameters($route, $callable);

        abort(204, headers: ['Precognition-Success' => 'true']);
    }
}
