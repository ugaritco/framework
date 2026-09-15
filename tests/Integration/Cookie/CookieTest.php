<?php

namespace Heritage\Tests\Integration\Cookie;

use Heritage\Http\Response;
use Heritage\Session\NullSessionHandler;
use Heritage\Support\Carbon;
use Heritage\Support\Facades\Exceptions;
use Heritage\Support\Facades\Route;
use Heritage\Support\Facades\Session;
use Heritage\Support\Str;
use Orchestra\Testbench\TestCase;

class CookieTest extends TestCase
{
    public function test_cookie_is_sent_back_with_proper_expire_time_when_should_expire_on_close()
    {
        $this->app['config']->set('session.expire_on_close', true);

        Route::get('/', function () {
            return 'hello world';
        })->middleware('web');

        $response = $this->get('/');
        $this->assertCount(2, $response->headers->getCookies());
        $this->assertEquals(0, $response->headers->getCookies()[1]->getExpiresTime());
    }

    public function test_cookie_is_sent_back_with_proper_expire_time_with_respect_to_lifetime()
    {
        $this->app['config']->set('session.expire_on_close', false);
        $this->app['config']->set('session.lifetime', 1);

        Route::get('/', function () {
            return 'hello world';
        })->middleware('web');

        Carbon::setTestNow($now = Carbon::now());
        $response = $this->get('/');
        $this->assertCount(2, $response->headers->getCookies());
        $this->assertEquals($now->addMinute()->getTimestamp(), $response->headers->getCookies()[1]->getExpiresTime());
    }

    protected function defineEnvironment($app)
    {
        Exceptions::spy()->shouldReceive('render')->andReturn(new Response);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'fake-null');

        Session::extend('fake-null', function () {
            return new NullSessionHandler;
        });
    }
}
