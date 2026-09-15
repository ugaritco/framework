<?php

namespace Heritage\Tests\Integration\Log;

use Heritage\Log\Events\MessageLogged;
use Heritage\Log\Logger;
use Heritage\Support\Facades\Event;
use Heritage\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class LoggingIntegrationTest extends TestCase
{
    public function testLoggingCanBeRunWithoutEncounteringExceptions()
    {
        $this->expectNotToPerformAssertions();

        Log::info('Hello World');
    }

    public function testCallingLoggerDirectlyDispatchesOneEvent()
    {
        Event::fake([MessageLogged::class]);

        $this->app->make(Logger::class)->debug('my debug message');

        Event::assertDispatchedTimes(MessageLogged::class, 1);
    }
}
