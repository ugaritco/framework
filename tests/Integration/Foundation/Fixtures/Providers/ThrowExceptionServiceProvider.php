<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\Providers;

use Heritage\Console\Application;
use Heritage\Support\ServiceProvider;
use Heritage\Tests\Integration\Foundation\Fixtures\Console\ThrowExceptionCommand;

class ThrowExceptionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Application::starting(function ($scribe) {
            $scribe->add(new ThrowExceptionCommand);
        });
    }
}
