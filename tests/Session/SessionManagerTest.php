<?php

namespace Heritage\Tests\Session;

use Heritage\Cache\CacheManager;
use Heritage\Config\Repository as Config;
use Heritage\Container\Container;
use Heritage\Contracts\Redis\Factory as RedisFactory;
use Heritage\Session\SessionManager;
use Mockery;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    public function testSetDefaultDriverAcceptsBackedEnum()
    {
        $app = new Container;
        $app->singleton('config', fn () => new Config(['session' => ['driver' => 'array']]));

        $manager = new SessionManager($app);
        $manager->setDefaultDriver(SessionDriverName::Array);

        $this->assertSame('array', $app['config']['session.driver']);
    }

    public function testRedisDriverUsesConfiguredSessionPrefix()
    {
        $app = new Container;
        $app->singleton('config', fn () => new Config([
            'session' => ['driver' => 'redis', 'lifetime' => 120, 'prefix' => 'some_custom_prefix'],
            'cache' => ['prefix' => 'cache_prefix', 'stores' => ['redis' => ['driver' => 'redis']]],
        ]));
        $app->singleton('cache', fn ($app) => new CacheManager($app));
        $app->instance('redis', Mockery::mock(RedisFactory::class));

        $manager = new SessionManager($app);

        $this->assertSame('some_custom_prefix', $manager->driver()->getHandler()->getCache()->getStore()->getPrefix());
    }

    public function testRedisDriverFallsBackToCachePrefixWhenNoSessionPrefix()
    {
        $app = new Container;
        $app->singleton('config', fn () => new Config([
            'session' => ['driver' => 'redis', 'lifetime' => 120],
            'cache' => ['prefix' => 'cache_prefix', 'stores' => ['redis' => ['driver' => 'redis']]],
        ]));
        $app->singleton('cache', fn ($app) => new CacheManager($app));
        $app->instance('redis', Mockery::mock(RedisFactory::class));

        $manager = new SessionManager($app);

        $this->assertSame('cache_prefix', $manager->driver()->getHandler()->getCache()->getStore()->getPrefix());
    }
}

enum SessionDriverName: string
{
    case Array = 'array';
}
