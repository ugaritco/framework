<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Cache\ArrayStore;
use Heritage\Cache\Repository;
use Heritage\Console\Events\ScheduledTaskFailed;
use Heritage\Console\Scheduling\CacheEventMutex;
use Heritage\Console\Scheduling\CacheSchedulingMutex;
use Heritage\Console\Scheduling\EventMutex;
use Heritage\Console\Scheduling\Schedule;
use Heritage\Console\Scheduling\SchedulingMutex;
use Heritage\Container\Container;
use Heritage\Contracts\Cache\Factory;
use Heritage\Contracts\Events\Dispatcher;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class CallbackSchedulingTest extends TestCase
{
    protected $log = [];

    protected function setUp(): void
    {
        parent::setUp();

        $cache = new class implements Factory
        {
            public $store;

            public function __construct()
            {
                $this->store = new Repository(new ArrayStore(true));
            }

            public function store($name = null)
            {
                return $this->store;
            }
        };

        $container = Container::getInstance();

        $container->instance(EventMutex::class, new CacheEventMutex($cache));
        $container->instance(SchedulingMutex::class, new CacheSchedulingMutex($cache));
    }

    public function testExecutionOrder(): void
    {
        $event = $this->app->make(Schedule::class)
            ->call($this->logger('call'))
            ->after($this->logger('after 1'))
            ->before($this->logger('before 1'))
            ->after($this->logger('after 2'))
            ->before($this->logger('before 2'));

        $this->scribe('schedule:run');

        $this->assertLogged('before 1', 'before 2', 'call', 'after 1', 'after 2');
    }

    public function testCallbacksCannotRunInBackground(): void
    {
        $this->expectException(RuntimeException::class);

        $this->app->make(Schedule::class)
            ->call($this->logger('call'))
            ->runInBackground();
    }

    public function testExceptionHandlingInCallback(): void
    {
        $event = $this->app->make(Schedule::class)
            ->call($this->logger('call'))
            ->name('test-event')
            ->withoutOverlapping();

        // Set up "before" and "after" hooks to ensure they're called
        $event->before($this->logger('before'))->after($this->logger('after'));

        // Register a hook to validate that the mutex was initially created
        $mutexWasCreated = false;
        $event->before(function () use (&$mutexWasCreated, $event) {
            $mutexWasCreated = $event->mutex->exists($event);
        });

        // We'll trigger an exception in an "after" hook to test exception handling
        $event->after(function () {
            throw new RuntimeException;
        });

        // Because exceptions are caught by the ScheduleRunCommand, we need to listen for
        // the "failed" event to check whether our exception was actually thrown
        $failed = false;
        $this->app->make(Dispatcher::class)
            ->listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $failure) use (&$failed, $event) {
                if ($failure->task === $event) {
                    $failed = true;
                }
            });

        $this->scribe('schedule:run');

        // Hooks and execution should happen in correct order
        $this->assertLogged('before', 'call', 'after');

        // Our exception should have resulted in a failure event
        $this->assertTrue($failed);

        // Validate that the mutex was originally created, but that it's since
        // been removed (even though an exception was thrown)
        $this->assertTrue($mutexWasCreated);
        $this->assertFalse($event->mutex->exists($event));
    }

    protected function logger($message)
    {
        return function () use ($message) {
            $this->log[] = $message;
        };
    }

    protected function assertLogged(...$message)
    {
        $this->assertEquals($message, $this->log);
    }
}
