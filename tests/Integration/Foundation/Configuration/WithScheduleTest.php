<?php

namespace Heritage\Tests\Integration\Foundation\Configuration;

use Heritage\Console\Scheduling\ScheduleListCommand;
use Heritage\Foundation\Application;
use Heritage\Support\Carbon;
use Orchestra\Testbench\TestCase;

class WithScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2023-01-01');
        ScheduleListCommand::resolveTerminalWidthUsing(fn () => 80);
    }

    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withSchedule(function ($schedule) {
                $schedule->command('schedule:clear-cache')->everyMinute();
            })
            ->withCommands([__DIR__.'/Fixtures/console.php'])
            ->create();
    }

    public function testDisplaySchedule()
    {
        $this->scribe(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('  0 * * * *  php scribe test:inspire .............. Next Due: 1 hour from now')
            ->expectsOutputToContain('  * * * * *  php scribe schedule:clear-cache .... Next Due: 1 minute from now');
    }
}
