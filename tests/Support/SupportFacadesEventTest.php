<?php

namespace Heritage\Tests\Support;

use Heritage\Cache\CacheManager;
use Heritage\Cache\Events\CacheFlushed;
use Heritage\Cache\Events\CacheFlushing;
use Heritage\Cache\Events\CacheLocksFlushed;
use Heritage\Cache\Events\CacheLocksFlushing;
use Heritage\Cache\Events\CacheMissed;
use Heritage\Cache\Events\RetrievingKey;
use Heritage\Config\Repository as ConfigRepository;
use Heritage\Container\Container;
use Heritage\Contracts\Events\Dispatcher as DispatcherContract;
use Heritage\Database\Eloquent\Model;
use Heritage\Events\Dispatcher;
use Heritage\Support\Facades\Cache;
use Heritage\Support\Facades\Event;
use Heritage\Support\Facades\Facade;
use Heritage\Support\Testing\Fakes\EventFake;
use Mockery;
use PHPUnit\Framework\TestCase;

class SupportFacadesEventTest extends TestCase
{
    private $events;

    protected function setUp(): void
    {
        $this->events = Mockery::mock(Dispatcher::class);

        $container = new Container;
        $container->instance('events', $this->events);
        $container->alias('events', DispatcherContract::class);
        $container->instance('cache', new CacheManager($container));
        $container->instance('config', new ConfigRepository($this->getCacheConfig()));

        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testFakeFor()
    {
        Event::fakeFor(function () {
            (new FakeForStub)->dispatch();

            Event::assertDispatched(EventStub::class);
        });

        $this->events->expects('dispatch');

        (new FakeForStub)->dispatch();
    }

    public function testFakeForSwapsDispatchers()
    {
        $arrayRepository = Cache::store('array');

        Event::fakeFor(function () use ($arrayRepository) {
            $this->assertInstanceOf(EventFake::class, Event::getFacadeRoot());
            $this->assertInstanceOf(EventFake::class, Model::getEventDispatcher());
            $this->assertInstanceOf(EventFake::class, $arrayRepository->getEventDispatcher());
        });

        $this->assertSame($this->events, Event::getFacadeRoot());
        $this->assertSame($this->events, Model::getEventDispatcher());
        $this->assertSame($this->events, $arrayRepository->getEventDispatcher());
    }

    public function testFakeSwapsDispatchersInResolvedCacheRepositories()
    {
        $arrayRepository = Cache::store('array');

        $this->events->expects('dispatch')->times(2);
        $arrayRepository->get('foo');

        Event::fake();

        $arrayRepository->get('bar');

        Event::assertDispatched(RetrievingKey::class);
        Event::assertDispatched(CacheMissed::class);
    }

    public function testCacheFlushDispatchesEvent()
    {
        $arrayRepository = Cache::store('array');
        Event::fake();

        $arrayRepository->clear();

        Event::assertDispatched(CacheFlushing::class);
        Event::assertDispatched(CacheFlushed::class);
    }

    public function testCacheFlushLocksDispatchesEvent()
    {
        $arrayRepository = Cache::store('array');
        Event::fake();

        $arrayRepository->flushLocks();

        Event::assertDispatched(CacheLocksFlushing::class);
        Event::assertDispatched(CacheLocksFlushed::class);
    }

    protected function getCacheConfig()
    {
        return [
            'cache' => [
                'stores' => [
                    'array' => [
                        'driver' => 'array',
                    ],
                ],
            ],
        ];
    }
}

class FakeForStub
{
    public function dispatch()
    {
        Event::dispatch(EventStub::class);
    }
}
