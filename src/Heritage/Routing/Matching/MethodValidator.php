<?php

namespace Heritage\Routing\Matching;

use Heritage\Http\Request;
use Heritage\Routing\Route;

class MethodValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param  \Heritage\Routing\Route  $route
     * @param  \Heritage\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        return in_array($request->getMethod(), $route->methods());
    }
}
