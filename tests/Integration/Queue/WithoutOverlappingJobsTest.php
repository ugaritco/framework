<?php

namespace Heritage\Tests\Integration\Queue;

use Exception;
use Heritage\Bus\Dispatcher;
use Heritage\Bus\Queueable;
use Heritage\Contracts\Cache\Repository as Cache;
use Heritage\Contracts\Queue\Job;
use Heritage\Queue\CallQueuedHandler;
use Heritage\Queue\InteractsWithQueue;
use Heritage\Queue\Middleware\WithoutOverlapping;
use Mockery;

class WithoutOverlappingJobsTest extends QueueTestCase
{
    public function testNonOverlappingJobsAreExecuted()
    {
        OverlappingTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(false);
        $job->expects('isDeletedOrReleased')->andReturn(false);
        $job->expects('delete');

        $instance->call($job, [
            'command' => serialize($command = new OverlappingTestJob),
        ]);

        $lockKey = (new WithoutOverlapping)->getLockKey($command);

        $this->assertTrue(OverlappingTestJob::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($lockKey, 10)->acquire());
    }

    public function testLockIsReleasedOnJobExceptions()
    {
        FailedOverlappingTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $this->expectException(Exception::class);

        try {
            $instance->call($job, [
                'command' => serialize($command = new FailedOverlappingTestJob),
            ]);
        } finally {
            $lockKey = (new WithoutOverlapping)->getLockKey($command);

            $this->assertTrue(FailedOverlappingTestJob::$handled);
            $this->assertTrue($this->app->get(Cache::class)->lock($lockKey, 10)->acquire());
        }
    }

    public function testOverlappingJobsAreReleased()
    {
        OverlappingTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $lockKey = (new WithoutOverlapping)->getLockKey($command = new OverlappingTestJob);
        $this->app->get(Cache::class)->lock($lockKey, 10)->acquire();

        $job = Mockery::mock(Job::class);

        $job->expects('release');
        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(true);
        $job->expects('isDeletedOrReleased')->andReturn(true);

        $instance->call($job, [
            'command' => serialize($command),
        ]);

        $this->assertFalse(OverlappingTestJob::$handled);
    }

    public function testOverlappingJobsCanBeSkipped()
    {
        SkipOverlappingTestJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $lockKey = (new WithoutOverlapping)->getLockKey($command = new SkipOverlappingTestJob);
        $this->app->get(Cache::class)->lock($lockKey, 10)->acquire();

        $job = Mockery::mock(Job::class);

        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(false);
        $job->expects('isDeletedOrReleased')->andReturn(false);
        $job->expects('delete');

        $instance->call($job, [
            'command' => serialize($command),
        ]);

        $this->assertFalse(SkipOverlappingTestJob::$handled);
    }

    public function testCanShareKeyAcrossJobs()
    {
        OverlappingTestJobWithSharedKeyOne::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $lockKey = (new WithoutOverlapping)->shared()->getLockKey(new OverlappingTestJobWithSharedKeyTwo);
        $this->app->get(Cache::class)->lock($lockKey, 10)->acquire();

        $job = Mockery::mock(Job::class);

        $job->expects('release');
        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(true);
        $job->expects('isDeletedOrReleased')->andReturn(true);

        $instance->call($job, [
            'command' => serialize(new OverlappingTestJobWithSharedKeyOne),
        ]);

        $this->assertFalse(OverlappingTestJob::$handled);
    }

    public function testGetLock()
    {
        $job = new OverlappingTestJob;

        $this->assertSame(
            'ugarit-queue-overlap:Heritage\\Tests\\Integration\\Queue\\OverlappingTestJob:key',
            (new WithoutOverlapping('key'))->getLockKey($job)
        );

        $this->assertSame(
            'ugarit-queue-overlap:key',
            (new WithoutOverlapping('key'))->shared()->getLockKey($job)
        );

        $this->assertSame(
            'prefix:Heritage\\Tests\\Integration\\Queue\\OverlappingTestJob:key',
            (new WithoutOverlapping('key'))->withPrefix('prefix:')->getLockKey($job)
        );

        $this->assertSame(
            'prefix:key',
            (new WithoutOverlapping('key'))->withPrefix('prefix:')->shared()->getLockKey($job)
        );
    }

    public function testGetLockUsesDisplayName()
    {
        $job = new OverlappingTestJobWithDisplayName;

        $this->assertSame(
            'ugarit-queue-overlap:'.hash('xxh128', 'App\\Actions\\WithoutOverlappingTestAction').':key',
            (new WithoutOverlapping('key'))->getLockKey($job)
        );

        $this->assertSame(
            'ugarit-queue-overlap:key',
            (new WithoutOverlapping('key'))->shared()->getLockKey($job)
        );

        $this->assertSame(
            'prefix:'.hash('xxh128', 'App\\Actions\\WithoutOverlappingTestAction').':key',
            (new WithoutOverlapping('key'))->withPrefix('prefix:')->getLockKey($job)
        );

        $this->assertSame(
            'prefix:key',
            (new WithoutOverlapping('key'))->withPrefix('prefix:')->shared()->getLockKey($job)
        );

        $this->assertSame(
            'prefix:'.hash('xxh128', 'App\\Actions\\WithoutOverlappingTestAction').':unit',
            (new WithoutOverlapping(UnitCategory::unit))->withPrefix('prefix:')->getLockKey($job)
        );

        $this->assertSame(
            'prefix:unit',
            (new WithoutOverlapping(UnitCategory::unit))->withPrefix('prefix:')->shared()->getLockKey($job)
        );

        $this->assertSame(
            'prefix:'.hash('xxh128', 'App\\Actions\\WithoutOverlappingTestAction').':backed',
            (new WithoutOverlapping(BackedCategory::backed))->withPrefix('prefix:')->getLockKey($job)
        );

        $this->assertSame(
            'prefix:backed',
            (new WithoutOverlapping(BackedCategory::backed))->withPrefix('prefix:')->shared()->getLockKey($job)
        );
    }
}

class OverlappingTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new WithoutOverlapping];
    }
}

class SkipOverlappingTestJob extends OverlappingTestJob
{
    public function middleware()
    {
        return [(new WithoutOverlapping)->dontRelease()];
    }
}

class FailedOverlappingTestJob extends OverlappingTestJob
{
    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }
}

class OverlappingTestJobWithSharedKeyOne
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [(new WithoutOverlapping)->shared()];
    }
}

class OverlappingTestJobWithSharedKeyTwo
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [(new WithoutOverlapping)->shared()];
    }
}

class OverlappingTestJobWithDisplayName extends OverlappingTestJob
{
    public function displayName(): string
    {
        return 'App\\Actions\\WithoutOverlappingTestAction';
    }
}

enum UnitCategory
{
    case unit;
}

enum BackedCategory: string
{
    case backed = 'backed';
}
