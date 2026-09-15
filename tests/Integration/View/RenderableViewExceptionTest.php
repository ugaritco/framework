<?php

namespace Heritage\Tests\Integration\View;

use Exception;
use Heritage\Http\Response;
use Heritage\Support\Facades\Route;
use Heritage\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class RenderableViewExceptionTest extends TestCase
{
    public function testRenderMethodOfExceptionThrownInViewGetsHandled()
    {
        Route::get('/', function () {
            return View::make('renderable-exception');
        });

        $response = $this->get('/');

        $response->assertSee('This is a renderable exception.');
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('view.paths', [__DIR__.'/Fixtures/templates']);
    }
}

class RenderableException extends Exception
{
    public function render($request)
    {
        return new Response('This is a renderable exception.');
    }
}
