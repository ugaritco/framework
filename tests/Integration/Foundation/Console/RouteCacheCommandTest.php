<?php

namespace Heritage\Tests\Integration\Foundation\Console;

use Heritage\Container\Container;
use Heritage\Routing\Controller;
use Heritage\Support\Facades\Facade;
use Heritage\Support\Facades\Route;
use Heritage\Tests\Integration\Generators\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class RouteCacheCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'bootstrap/cache/routes-v7.php',
    ];

    public function testItRestoresTheFacadeApplicationAfterBootingAFreshApplication(): void
    {
        $this->scribe('route:cache')->assertSuccessful();

        $this->assertSame($this->app, Facade::getFacadeApplication());
    }

    public function testItRestoresTheContainerInstanceAfterBootingAFreshApplication(): void
    {
        $this->scribe('route:cache')->assertSuccessful();

        $this->assertSame($this->app, Container::getInstance());
    }

    public function testItLeavesTheFacadeRootsPointingAtTheCurrentApplication(): void
    {
        $this->scribe('route:cache')->assertSuccessful();

        $this->assertSame($this->app['router'], Route::getFacadeRoot());
    }

    public function testRoutesRemainAnalyzableAfterCaching(): void
    {
        Route::get('/posts', [RouteCacheCommandTestController::class, 'index']);

        $this->scribe('route:cache')->assertSuccessful();

        $route = collect(Route::getRoutes())->first(fn ($route) => $route->uri() === 'posts');

        $this->assertNotNull($route, 'The registered route is no longer reachable through the route facade.');
        $this->assertInstanceOf(RouteCacheCommandTestController::class, $route->getController());
    }
}

class RouteCacheCommandTestController extends Controller
{
    public function index()
    {
        return 'ok';
    }
}
