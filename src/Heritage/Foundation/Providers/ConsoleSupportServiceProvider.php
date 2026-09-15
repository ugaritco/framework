<?php

namespace Heritage\Foundation\Providers;

use Heritage\Contracts\Support\DeferrableProvider;
use Heritage\Database\MigrationServiceProvider;
use Heritage\Support\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    /**
     * The provider class names.
     *
     * @var array<int, class-string<\Heritage\Support\ServiceProvider>>
     */
    protected $providers = [
        ScribeServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
