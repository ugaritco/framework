<?php

namespace Heritage\Routing\Matching;

use Heritage\Http\Request;
use Heritage\Routing\Route;

interface ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param  \Heritage\Routing\Route  $route
     * @param  \Heritage\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request);
}
