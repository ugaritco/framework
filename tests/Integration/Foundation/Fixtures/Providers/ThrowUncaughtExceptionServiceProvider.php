<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\Providers;

use Heritage\Console\Application;
use Heritage\Support\ServiceProvider;
use Heritage\Tests\Integration\Foundation\Fixtures\Console\ThrowExceptionCommand;
use Heritage\Tests\Integration\Foundation\Fixtures\Logs\ThrowExceptionLogHandler;

class ThrowUncaughtExceptionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $config = $this->app['config'];

        $config->set('logging.default', 'throw_exception');

        $config->set('logging.channels.throw_exception', [
            'driver' => 'monolog',
            'handler' => ThrowExceptionLogHandler::class,
        ]);
    }

    public function boot()
    {
        Application::starting(function ($scribe) {
            $scribe->add(new ThrowExceptionCommand);
        });
    }
}
