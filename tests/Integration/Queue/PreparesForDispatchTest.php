<?php

namespace Heritage\Tests\Integration\Queue;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Queue\PreparesForDispatch;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Bus\Dispatchable;
use Heritage\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class PreparesForDispatchTest extends TestCase
{
    public function test_does_not_dispatch_when_prepare_returns_false()
    {
        Queue::fake();

        PreparesForDispatchFalseJob::dispatch();

        Queue::assertNotPushed(PreparesForDispatchFalseJob::class);
    }

    public function test_dispatches_when_prepare_returns_void()
    {
        Queue::fake();

        PreparesForDispatchVoidJob::$ran = false;

        PreparesForDispatchVoidJob::dispatch();

        $this->assertTrue(PreparesForDispatchVoidJob::$ran);
        Queue::assertPushed(PreparesForDispatchVoidJob::class);
    }
}

class PreparesForDispatchFalseJob implements PreparesForDispatch, ShouldQueue
{
    use Dispatchable, Queueable;

    public function prepareForDispatch(): bool
    {
        return false;
    }

    public function handle(): void
    {
    }
}

class PreparesForDispatchVoidJob implements PreparesForDispatch, ShouldQueue
{
    use Dispatchable, Queueable;

    public static bool $ran = false;

    public function prepareForDispatch(): void
    {
        static::$ran = true;
    }

    public function handle(): void
    {
    }
}
