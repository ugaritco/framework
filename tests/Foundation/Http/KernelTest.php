<?php

namespace Heritage\Tests\Foundation\Http;

use Heritage\Events\Dispatcher;
use Heritage\Foundation\Application;
use Heritage\Foundation\Events\Terminating;
use Heritage\Foundation\Http\Kernel;
use Heritage\Http\Request;
use Heritage\Http\Response;
use Heritage\Routing\Router;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function testGetMiddlewareGroups()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $this->assertSame([], $kernel->getMiddlewareGroups());
    }

    public function testGetRouteMiddleware()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $this->assertSame([], $kernel->getRouteMiddleware());
    }

    public function testGetMiddlewarePriority()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $this->assertEquals([
            \Heritage\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Heritage\Cookie\Middleware\EncryptCookies::class,
            \Heritage\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Heritage\Session\Middleware\StartSession::class,
            \Heritage\View\Middleware\ShareErrorsFromSession::class,
            \Heritage\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Heritage\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Heritage\Routing\Middleware\SubstituteBindings::class,
            \Heritage\Auth\Middleware\Authorize::class,
        ], $kernel->getMiddlewarePriority());
    }

    public function testAddToMiddlewarePriorityAfter()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $kernel->addToMiddlewarePriorityAfter(
            [
                \Heritage\Cookie\Middleware\EncryptCookies::class,
                \Heritage\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            ],
            \Heritage\Routing\Middleware\ValidateSignature::class,
        );

        $this->assertEquals([
            \Heritage\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Heritage\Cookie\Middleware\EncryptCookies::class,
            \Heritage\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Heritage\Session\Middleware\StartSession::class,
            \Heritage\View\Middleware\ShareErrorsFromSession::class,
            \Heritage\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Heritage\Routing\Middleware\ValidateSignature::class,
            \Heritage\Routing\Middleware\ThrottleRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Heritage\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Heritage\Routing\Middleware\SubstituteBindings::class,
            \Heritage\Auth\Middleware\Authorize::class,
        ], $kernel->getMiddlewarePriority());
    }

    public function testAddToMiddlewarePriorityAfterFirstMiddleware()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $kernel->addToMiddlewarePriorityAfter(
            \Heritage\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Heritage\Routing\Middleware\ValidateSignature::class,
        );

        $this->assertEquals([
            \Heritage\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Heritage\Routing\Middleware\ValidateSignature::class,
            \Heritage\Cookie\Middleware\EncryptCookies::class,
            \Heritage\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Heritage\Session\Middleware\StartSession::class,
            \Heritage\View\Middleware\ShareErrorsFromSession::class,
            \Heritage\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Heritage\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Heritage\Routing\Middleware\SubstituteBindings::class,
            \Heritage\Auth\Middleware\Authorize::class,
        ], $kernel->getMiddlewarePriority());
    }

    public function testAddToMiddlewarePriorityAfterAdjacentMiddleware()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $kernel->addToMiddlewarePriorityAfter(
            [
                \Heritage\Cookie\Middleware\EncryptCookies::class,
                \Heritage\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            ],
            \Heritage\Routing\Middleware\ValidateSignature::class,
        );

        $this->assertEquals([
            \Heritage\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Heritage\Cookie\Middleware\EncryptCookies::class,
            \Heritage\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Heritage\Routing\Middleware\ValidateSignature::class,
            \Heritage\Session\Middleware\StartSession::class,
            \Heritage\View\Middleware\ShareErrorsFromSession::class,
            \Heritage\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Heritage\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Heritage\Routing\Middleware\SubstituteBindings::class,
            \Heritage\Auth\Middleware\Authorize::class,
        ], $kernel->getMiddlewarePriority());
    }

    public function testAddToMiddlewarePriorityBefore()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $kernel->addToMiddlewarePriorityBefore(
            [
                \Heritage\Cookie\Middleware\EncryptCookies::class,
                \Heritage\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            ],
            \Heritage\Routing\Middleware\ValidateSignature::class,
        );

        $this->assertEquals([
            \Heritage\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Heritage\Routing\Middleware\ValidateSignature::class,
            \Heritage\Cookie\Middleware\EncryptCookies::class,
            \Heritage\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Heritage\Session\Middleware\StartSession::class,
            \Heritage\View\Middleware\ShareErrorsFromSession::class,
            \Heritage\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequests::class,
            \Heritage\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Heritage\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Heritage\Routing\Middleware\SubstituteBindings::class,
            \Heritage\Auth\Middleware\Authorize::class,
        ], $kernel->getMiddlewarePriority());
    }

    public function testItTriggersTerminatingEvent()
    {
        $called = [];
        $app = $this->getApplication();
        $events = new Dispatcher($app);
        $app->instance('events', $events);
        $kernel = new Kernel($app, $this->getRouter());
        $app->instance('terminating-middleware', new class($called)
        {
            public function __construct(private &$called)
            {
                //
            }

            public function handle($request, $next)
            {
                return $next($request);
            }

            public function terminate($request, $response)
            {
                $this->called[] = 'terminating middleware';
            }
        });
        $kernel->setGlobalMiddleware([
            'terminating-middleware',
        ]);
        $events->listen(function (Terminating $terminating) use (&$called) {
            $called[] = 'terminating event';
        });
        $app->terminating(function () use (&$called) {
            $called[] = 'terminating callback';
        });

        $kernel->terminate(new Request(), new Response());

        $this->assertSame([
            'terminating event',
            'terminating middleware',
            'terminating callback',
        ], $called);
    }

    /**
     * @return \Heritage\Contracts\Foundation\Application
     */
    protected function getApplication()
    {
        return new Application;
    }

    /**
     * @return \Heritage\Routing\Router
     */
    protected function getRouter()
    {
        return new Router(new Dispatcher);
    }
}
