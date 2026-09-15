<?php

namespace Heritage\Tests\Integration\Routing;

use Heritage\Support\Facades\Route;
use Heritage\Tests\Routing\Fixtures\RouteNameEnum;
use Orchestra\Testbench\TestCase;

class SimpleRouteTest extends TestCase
{
    public function testSimpleRouteThroughTheFramework()
    {
        Route::get('/', function () {
            return 'Hello World';
        });

        $response = $this->get('/');

        $this->assertSame('Hello World', $response->content());

        $response = $this->get('/?foo=bar');

        $this->assertSame('Hello World', $response->content());

        $this->assertSame('bar', $response->baseRequest->query('foo'));
    }

    public function testSimpleRouteWitStringBackedEnumRouteNameThroughTheFramework()
    {
        Route::get('/', function () {
            return 'Hello World';
        })->name(RouteNameEnum::UserIndex);

        $response = $this->get(\route(RouteNameEnum::UserIndex, ['foo' => 'bar']));

        $this->assertSame('Hello World', $response->content());

        $this->assertSame('bar', $response->baseRequest->query('foo'));
    }
}
