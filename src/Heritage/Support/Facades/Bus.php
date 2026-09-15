<?php

namespace Heritage\Support\Facades;

use Heritage\Bus\BatchRepository;
use Heritage\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Heritage\Foundation\Bus\PendingChain;
use Heritage\Support\Testing\Fakes\BusFake;

/**
 * @method static mixed dispatch(mixed $command)
 * @method static mixed dispatchSync(mixed $command, mixed $handler = null)
 * @method static mixed dispatchNow(mixed $command, mixed $handler = null)
 * @method static void bulk(iterable $jobs)
 * @method static \Heritage\Bus\Batch|null findBatch(string $batchId)
 * @method static \Heritage\Bus\PendingBatch batch(\Heritage\Support\Collection|mixed $jobs)
 * @method static \Heritage\Foundation\Bus\PendingChain chain(\Heritage\Support\Collection|array|null $jobs = null)
 * @method static bool hasCommandHandler(mixed $command)
 * @method static mixed getCommandHandler(mixed $command)
 * @method static mixed dispatchToQueue(mixed $command)
 * @method static void dispatchAfterResponse(mixed $command, mixed $handler = null)
 * @method static \Heritage\Bus\Dispatcher pipeThrough(array $pipes)
 * @method static \Heritage\Bus\Dispatcher map(array $map)
 * @method static \Heritage\Bus\Dispatcher withDispatchingAfterResponses()
 * @method static \Heritage\Bus\Dispatcher withoutDispatchingAfterResponses()
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
 * @method static \Heritage\Support\Testing\Fakes\BusFake except(array|string $jobsToDispatch)
 * @method static void assertDispatched(string|\Closure $command, callable|int|null $callback = null)
 * @method static void assertDispatchedOnce(string|\Closure $command)
 * @method static void assertDispatchedTimes(string|\Closure $command, int $times = 1)
 * @method static void assertNotDispatched(string|\Closure $command, callable|null $callback = null)
 * @method static void assertNothingDispatched()
 * @method static void assertDispatchedSync(string|\Closure $command, callable|int|null $callback = null)
 * @method static void assertDispatchedSyncTimes(string|\Closure $command, int $times = 1)
 * @method static void assertNotDispatchedSync(string|\Closure $command, callable|null $callback = null)
 * @method static void assertDispatchedAfterResponse(string|\Closure $command, callable|int|null $callback = null)
 * @method static void assertDispatchedAfterResponseTimes(string|\Closure $command, int $times = 1)
 * @method static void assertNotDispatchedAfterResponse(string|\Closure $command, callable|null $callback = null)
 * @method static void assertChained(array $expectedChain)
 * @method static void assertNothingChained()
 * @method static void assertDispatchedWithoutChain(string|\Closure $command, callable|null $callback = null)
 * @method static \Heritage\Support\Testing\Fakes\ChainedBatchTruthTest chainedBatch(\Closure $callback)
 * @method static void assertBatched(array|callable $callback)
 * @method static void assertBatchCount(int $count)
 * @method static void assertNothingBatched()
 * @method static void assertNothingPlaced()
 * @method static \Heritage\Support\Collection dispatched(string $command, callable|null $callback = null)
 * @method static \Heritage\Support\Collection dispatchedSync(string $command, callable|null $callback = null)
 * @method static \Heritage\Support\Collection dispatchedAfterResponse(string $command, callable|null $callback = null)
 * @method static \Heritage\Support\Collection batched(callable $callback)
 * @method static bool hasDispatched(string $command)
 * @method static bool hasDispatchedSync(string $command)
 * @method static bool hasDispatchedAfterResponse(string $command)
 * @method static \Heritage\Bus\Batch dispatchFakeBatch(string $name = '')
 * @method static \Heritage\Bus\Batch recordPendingBatch(\Heritage\Bus\PendingBatch $pendingBatch)
 * @method static \Heritage\Support\Testing\Fakes\BusFake serializeAndRestore(bool $serializeAndRestore = true)
 * @method static array dispatchedBatches()
 *
 * @see \Heritage\Bus\Dispatcher
 * @see \Heritage\Support\Testing\Fakes\BusFake
 */
class Bus extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $jobsToFake
     * @param  \Heritage\Bus\BatchRepository|null  $batchRepository
     * @return \Heritage\Support\Testing\Fakes\BusFake
     */
    public static function fake($jobsToFake = [], ?BatchRepository $batchRepository = null)
    {
        $actualDispatcher = static::isFake()
            ? static::getFacadeRoot()->dispatcher
            : static::getFacadeRoot();

        return tap(new BusFake($actualDispatcher, $jobsToFake, $batchRepository), function ($fake) {
            static::swap($fake);
        });
    }

    /**
     * Dispatch the given chain of jobs.
     *
     * @param  mixed  $jobs
     * @return \Heritage\Foundation\Bus\PendingDispatch
     */
    public static function dispatchChain($jobs)
    {
        $jobs = is_array($jobs) ? $jobs : func_get_args();

        return (new PendingChain(array_shift($jobs), $jobs))
            ->dispatch();
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BusDispatcherContract::class;
    }
}
