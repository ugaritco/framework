<?php

namespace Heritage\Tests\Integration\Foundation\Console;

use Heritage\Foundation\Console\ClosureCommand;
use Heritage\Support\ServiceProvider;
use Heritage\Tests\Integration\Generators\TestCase;

class OptimizeClearCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProviderWithOptimizeClear::class];
    }

    public function testCanListenToOptimizingEvent(): void
    {
        $this->withoutDeprecationHandling();

        $this->scribe('optimize:clear')
            ->assertSuccessful()
            ->expectsOutputToContain('ServiceProviderWithOptimizeClear');
    }

    public function testCanExcludeCommandsByKey(): void
    {
        $this->scribe('optimize:clear', ['--except' => 'my package'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my package');
    }

    public function testCanExcludeCommandsByCommand(): void
    {
        $this->scribe('optimize:clear', ['--except' => 'my_package:cache'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my_package:cache');
    }
}

class ServiceProviderWithOptimizeClear extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            new ClosureCommand('my_package:clear', fn () => 0),
        ]);

        $this->optimizes(
            clear: 'my_package:clear',
        );
    }
}
