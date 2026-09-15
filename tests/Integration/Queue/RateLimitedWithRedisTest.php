<?php

namespace Heritage\Tests\Integration\Queue;

use Heritage\Bus\Dispatcher;
use Heritage\Bus\Queueable;
use Heritage\Cache\RateLimiter;
use Heritage\Cache\RateLimiting\Limit;
use Heritage\Contracts\Queue\Job;
use Heritage\Contracts\Redis\Connection;
use Heritage\Foundation\Testing\Concerns\InteractsWithRedis;
use Heritage\Queue\CallQueuedHandler;
use Heritage\Queue\InteractsWithQueue;
use Heritage\Queue\Middleware\RateLimitedWithRedis;
use Heritage\Support\Str;
use Mockery;
use Orchestra\Testbench\Attributes\RequiresEnv;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresEnv('REDIS_CLIENT')]
#[RequiresPhpExtension('redis')]
class RateLimitedWithRedisTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    public function testUnlimitedJobsAreExecuted()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $testJob = new RedisRateLimitedTestJob;

        $rateLimiter->for($testJob->key, function ($job) {
            return Limit::none();
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobRanSuccessfully($testJob);
    }

    public function testRateLimitedJobsAreNotExecutedOnLimitReached()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $testJob = new RedisRateLimitedTestJob;

        $rateLimiter->for($testJob->key, function ($job) {
            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobWasReleased($testJob);
    }

    public function testRateLimitedJobsCanBeSkippedOnLimitReached()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $testJob = new RedisRateLimitedDontReleaseTestJob;

        $rateLimiter->for($testJob->key, function ($job) {
            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobWasSkipped($testJob);
    }

    public function testJobsCanHaveConditionalRateLimits()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);

        $adminJob = new RedisAdminTestJob;

        $rateLimiter->for($adminJob->key, function ($job) {
            if ($job->isAdmin()) {
                return Limit::none();
            }

            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($adminJob);
        $this->assertJobRanSuccessfully($adminJob);

        $nonAdminJob = new RedisNonAdminTestJob;

        $rateLimiter->for($nonAdminJob->key, function ($job) {
            if ($job->isAdmin()) {
                return Limit::none();
            }

            return Limit::perMinute(1);
        });

        $this->assertJobRanSuccessfully($nonAdminJob);
        $this->assertJobWasReleased($nonAdminJob);
    }

    public function testLimitsAreNotHitWhenAnotherLimitIsReached()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);
        $testJob = new RedisRateLimitedTestJob;

        $rateLimiter->for($testJob->key, function () {
            return [
                Limit::perHour(10)->by('global'),
                Limit::perHour(1)->by('tenant'),
            ];
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobWasReleased($testJob);
        $this->assertJobWasReleased($testJob);

        $redis = $this->app->make('redis')->connection();

        $this->assertSame(1, (int) $redis->hget(md5($testJob->key.'global'), 'count'));
        $this->assertSame(1, (int) $redis->hget(md5($testJob->key.'tenant'), 'count'));
    }

    public function testLimitsAreNotHitWhenAnotherLimitIsReachedAndJobIsSkipped()
    {
        $rateLimiter = $this->app->make(RateLimiter::class);
        $testJob = new RedisRateLimitedDontReleaseTestJob;

        $rateLimiter->for($testJob->key, function () {
            return [
                Limit::perHour(10)->by('global'),
                Limit::perHour(1)->by('tenant'),
            ];
        });

        $this->assertJobRanSuccessfully($testJob);
        $this->assertJobWasSkipped($testJob);
        $this->assertJobWasSkipped($testJob);

        $redis = $this->app->make('redis')->connection();

        $this->assertSame(1, (int) $redis->hget(md5($testJob->key.'global'), 'count'));
        $this->assertSame(1, (int) $redis->hget(md5($testJob->key.'tenant'), 'count'));
    }

    public function testMiddlewareSerialization()
    {
        $rateLimited = new RateLimitedWithRedis('limiterName', 'default');
        $rateLimited->shouldRelease = false;

        $restoredRateLimited = unserialize(serialize($rateLimited));

        $fetch = (function (string $name) {
            return $this->{$name};
        })->bindTo($restoredRateLimited, RateLimitedWithRedis::class);

        $this->assertFalse($restoredRateLimited->shouldRelease);
        $this->assertSame('limiterName', $fetch('limiterName'));
        $this->assertSame('default', $fetch('connectionName'));
        $this->assertInstanceOf(RateLimiter::class, $fetch('limiter'));
        // $this->assertInstanceOf(Connection::class, $fetch('redis'));
    }

    protected function assertJobRanSuccessfully($testJob)
    {
        $testJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(false);
        $job->expects('isDeletedOrReleased')->andReturn(false);
        $job->expects('delete');

        $instance->call($job, [
            'command' => serialize($testJob),
        ]);

        $this->assertTrue($testJob::$handled);
    }

    protected function assertJobWasReleased($testJob)
    {
        $testJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->expects('hasFailed')->andReturn(false);
        $job->expects('release');
        $job->expects('isReleased')->times(2)->andReturn(true);
        $job->expects('isDeletedOrReleased')->andReturn(true);

        $instance->call($job, [
            'command' => serialize($testJob),
        ]);

        $this->assertFalse($testJob::$handled);
    }

    protected function assertJobWasSkipped($testJob)
    {
        $testJob::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = Mockery::mock(Job::class);

        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(false);
        $job->expects('isDeletedOrReleased')->andReturn(false);
        $job->expects('delete');

        $instance->call($job, [
            'command' => serialize($testJob),
        ]);

        $this->assertFalse($testJob::$handled);
    }
}

class RedisRateLimitedTestJob
{
    use InteractsWithQueue, Queueable;

    public $key;

    public static $handled = false;

    public function __construct()
    {
        $this->key = Str::random(10);
    }

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new RateLimitedWithRedis($this->key)];
    }
}

class RedisAdminTestJob extends RedisRateLimitedTestJob
{
    public function isAdmin()
    {
        return true;
    }
}

class RedisNonAdminTestJob extends RedisRateLimitedTestJob
{
    public function isAdmin()
    {
        return false;
    }
}

class RedisRateLimitedDontReleaseTestJob extends RedisRateLimitedTestJob
{
    public function middleware()
    {
        return [(new RateLimitedWithRedis($this->key))->dontRelease()];
    }
}
