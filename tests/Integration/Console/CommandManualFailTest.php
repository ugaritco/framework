<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Console\Application as Scribe;
use Heritage\Console\Command;
use Heritage\Console\ManuallyFailedException;
use Orchestra\Testbench\TestCase;

class CommandManualFailTest extends TestCase
{
    protected function setUp(): void
    {
        Scribe::starting(function ($scribe) {
            $scribe->resolveCommands([
                FailingCommandStub::class,
            ]);
        });

        parent::setUp();
    }

    public function testFailScribeCommandManually(): void
    {
        $this->scribe('app:fail')->assertFailed();
    }

    public function testCreatesAnExceptionFromString(): void
    {
        $this->expectExceptionObject(new ManuallyFailedException('Whoops!'));
        $command = new Command;
        $command->fail('Whoops!');
    }

    public function testCreatesAnExceptionFromNull(): void
    {
        $this->expectExceptionObject(new ManuallyFailedException('Command failed manually.'));
        $command = new Command;
        $command->fail();
    }

    public function testThrowsTheOriginalThrowableInstance(): void
    {
        try {
            $command = new Command;
            $command->fail($original = new \RuntimeException('Something went wrong.'));

            $this->fail('Command::fail() method must throw the original throwable instance.');
        } catch (\Throwable $e) {
            $this->assertSame($original, $e);
        }
    }
}

class FailingCommandStub extends Command
{
    protected $signature = 'app:fail';

    public function handle()
    {
        $this->trigger_failure();

        // This should never be reached.
        return static::SUCCESS;
    }

    protected function trigger_failure()
    {
        $this->fail('Whoops!');
    }
}
