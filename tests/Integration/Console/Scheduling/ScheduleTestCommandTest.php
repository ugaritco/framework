<?php

namespace Heritage\Tests\Integration\Console\Scheduling;

use Heritage\Console\Application;
use Heritage\Console\Command;
use Heritage\Console\Scheduling\Schedule;
use Heritage\Console\Scheduling\ScheduleTestCommand;
use Heritage\Support\Carbon;
use Orchestra\Testbench\TestCase;

class ScheduleTestCommandTest extends TestCase
{
    public $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now()->startOfYear());

        $this->schedule = $this->app->make(Schedule::class);
    }

    public function testRunNoDefinedCommands()
    {
        $this->scribe(ScheduleTestCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('No scheduled commands have been defined.');
    }

    public function testRunNoMatchingCommand()
    {
        $this->schedule->command(BarCommandStub::class);

        $this->scribe(ScheduleTestCommand::class, ['--name' => 'missing:command'])
            ->assertSuccessful()
            ->expectsOutputToContain('No matching scheduled command found.');
    }

    public function testRunUsingNameOption()
    {
        $this->schedule->command(BarCommandStub::class)->name('bar-command');
        $this->schedule->job(BarJobStub::class);
        $this->schedule->call(fn () => true)->name('callback');

        $expectedOutput = windows_os()
            ? 'Running ["scribe" bar:command]'
            : "Running ['scribe' bar:command]";

        $this->scribe(ScheduleTestCommand::class, ['--name' => 'bar:command'])
            ->assertSuccessful()
            ->expectsOutputToContain($expectedOutput);

        $this->scribe(ScheduleTestCommand::class, ['--name' => BarJobStub::class])
            ->assertSuccessful()
            ->expectsOutputToContain(sprintf('Running [%s]', BarJobStub::class));

        $this->scribe(ScheduleTestCommand::class, ['--name' => 'callback'])
            ->assertSuccessful()
            ->expectsOutputToContain('Running [callback]');
    }

    public function testRunUsingChoices()
    {
        $this->schedule->command(BarCommandStub::class)->name('bar-command');
        $this->schedule->job(BarJobStub::class);
        $this->schedule->call(fn () => true)->name('callback');

        $this->scribe(ScheduleTestCommand::class)
            ->assertSuccessful()
            ->expectsChoice(
                'Which command would you like to run?',
                'callback',
                [Application::formatCommandString('bar:command'), BarJobStub::class, 'callback'],
                true
            )
            ->expectsOutputToContain('Running [callback]');
    }
}

class BarCommandStub extends Command
{
    protected $signature = 'bar:command';

    protected $description = 'This is the description of the command.';
}

class BarJobStub
{
    public function __invoke()
    {
        // ..
    }
}
