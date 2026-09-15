<?php

namespace Heritage\Tests\Integration\Routing;

use Heritage\Routing\Controllers\HasMiddleware;
use Heritage\Routing\Controllers\Middleware;
use Heritage\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class HasMiddlewareTest extends TestCase
{
    public function test_has_middleware_is_respected()
    {
        $route = Route::get('/', [HasMiddlewareTestController::class, 'index']);
        $this->assertEquals(['all', 'only-index'], $route->controllerMiddleware());

        $route = Route::get('/', [HasMiddlewareTestController::class, 'show']);
        $this->assertEquals(['all', 'except-index'], $route->controllerMiddleware());
    }
}

class HasMiddlewareTestController implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('all'),
            (new Middleware('only-index'))->only('index'),
            (new Middleware('except-index'))->except('index'),
        ];
    }

    public function index()
    {
        //
    }

    public function show()
    {
    }
}
