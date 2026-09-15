<?php

namespace Heritage\Routing\Controllers;

interface HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int,\Heritage\Routing\Controllers\Middleware|\Closure|string>
     */
    public static function middleware();
}
