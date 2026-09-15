<?php

namespace Heritage\Tests\Integration\Routing;

use Heritage\Routing\Attributes\Controllers\Authorize;
use Heritage\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class AuthorizeMiddlewareAttributeTest extends TestCase
{
    public function test_attribute_is_respected(): void
    {
        $route = Route::get('/', [AuthorizeMiddlewareAttributeController::class, 'index']);
        $this->assertEquals([
            'Heritage\Auth\Middleware\Authorize:all',
            'Heritage\Auth\Middleware\Authorize:only-index,a',
            'Heritage\Auth\Middleware\Authorize:also-index',
        ], $route->controllerMiddleware());

        $route = Route::get('/', [AuthorizeMiddlewareAttributeController::class, 'show']);
        $this->assertEquals([
            'Heritage\Auth\Middleware\Authorize:all',
            'Heritage\Auth\Middleware\Authorize:except-index,a,b',
        ], $route->controllerMiddleware());
    }
}

#[Authorize('all')]
#[Authorize('only-index', 'a', only: ['index'])]
#[Authorize('except-index', ['a', 'b'], except: ['index'])]
class AuthorizeMiddlewareAttributeController
{
    #[Authorize('also-index')]
    public function index(): void
    {
        // ...
    }

    public function show(): void
    {
        // ...
    }
}
