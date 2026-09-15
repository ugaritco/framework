<?php

namespace Heritage\Tests\Integration\Generators;

class ProviderMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Providers/FooServiceProvider.php',
    ];

    public function testItCanGenerateServiceProviderFile()
    {
        $this->scribe('make:provider', ['name' => 'FooServiceProvider'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Providers;',
            'use Heritage\Support\ServiceProvider;',
            'class FooServiceProvider extends ServiceProvider',
            'public function register()',
            'public function boot()',
        ], 'app/Providers/FooServiceProvider.php');

        $this->assertEquals([
            'App\Providers\FooServiceProvider',
        ], require $this->app->getBootstrapProvidersPath());
    }
}
