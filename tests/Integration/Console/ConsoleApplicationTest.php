<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Console\Application as Scribe;
use Heritage\Console\Command;
use Heritage\Console\Scheduling\Schedule;
use Heritage\Contracts\Console\Kernel;
use Heritage\Foundation\Console\QueuedCommand;
use Heritage\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;

class ConsoleApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        Scribe::starting(function ($scribe) {
            $scribe->resolveCommands([
                FooCommandStub::class,
                ZondaCommandStub::class,
            ]);
        });

        parent::setUp();
    }

    public function testScribeCallUsingCommandName(): void
    {
        $this->scribe('foo:bar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testScribeCallUsingCommandNameAliases(): void
    {
        $this->scribe('app:foobar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testScribeCallUsingCommandClass(): void
    {
        $this->scribe(FooCommandStub::class, [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testScribeCallUsingCommandNameUsingAsCommandAttribute(): void
    {
        $this->scribe('zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testScribeCallUsingCommandNameAliasesUsingAsCommandAttribute(): void
    {
        $this->scribe('app:zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testScribeCallNow(): void
    {
        $exitCode = $this->scribe('foo:bar', [
            'id' => 1,
        ])->run();

        $this->assertSame(0, $exitCode);
    }

    public function testScribeWithMockCallAfterCallNow(): void
    {
        $exitCode = $this->scribe('foo:bar', [
            'id' => 1,
        ])->run();

        $mock = $this->scribe('foo:bar', [
            'id' => 1,
        ]);

        $this->assertSame(0, $exitCode);
        $mock->assertExitCode(0);
    }

    public function testScribeInstantiateScheduleWhenNeed(): void
    {
        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->app[Kernel::class]->registerCommand(new ScheduleCommandStub);

        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->scribe('foo:schedule');

        $this->assertTrue($this->app->resolved(Schedule::class));
    }

    public function testScribeQueue(): void
    {
        Queue::fake();

        $this->app[Kernel::class]->queue('foo:bar', [
            'id' => 1,
        ]);

        Queue::assertPushed(QueuedCommand::class, function ($job) {
            return $job->displayName() === 'foo:bar';
        });
    }
}

class FooCommandStub extends Command
{
    protected $signature = 'foo:bar {id}';

    protected $aliases = ['app:foobar'];

    public function handle()
    {
        //
    }
}

#[AsCommand(name: 'zonda', aliases: ['app:zonda'])]
class ZondaCommandStub extends Command
{
    protected $signature = 'zonda {id}';

    protected $aliases = ['app:zonda'];

    public function handle()
    {
        //
    }
}

class ScheduleCommandStub extends Command
{
    protected $signature = 'foo:schedule';

    public function handle(Schedule $schedule)
    {
        //
    }
}
