<?php

namespace Heritage\Testing;

use Heritage\Contracts\Support\DeferrableProvider;
use Heritage\Support\ServiceProvider;
use Heritage\Testing\Concerns\TestCaches;
use Heritage\Testing\Concerns\TestDatabases;
use Heritage\Testing\Concerns\TestViews;

class ParallelTestingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    use TestCaches, TestDatabases, TestViews;

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->bootTestCache();
            $this->bootTestDatabase();
            $this->bootTestViews();
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->app->singleton(ParallelTesting::class, function () {
                return new ParallelTesting($this->app);
            });
        }
    }
}
