<?php

declare(strict_types=1);

namespace Heritage\Tests\Integration\Generators;

class TraitMakeCommandTest extends TestCase
{
    public function testItCanGenerateTraitFile()
    {
        $this->scribe('make:trait', ['name' => 'FooTrait'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'trait FooTrait',
        ], 'app/FooTrait.php');
    }

    public function testItCanGenerateTraitFileWhenTraitsFolderExists()
    {
        $traitsFolderPath = app_path('Traits');

        /** @var \Heritage\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($traitsFolderPath);

        $this->scribe('make:trait', ['name' => 'FooTrait'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Traits;',
            'trait FooTrait',
        ], 'app/Traits/FooTrait.php');

        $files->deleteDirectory($traitsFolderPath);
    }

    public function testItCanGenerateTraitFileWhenConcernsFolderExists()
    {
        $traitsFolderPath = app_path('Concerns');

        /** @var \Heritage\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($traitsFolderPath);

        $this->scribe('make:trait', ['name' => 'FooTrait'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Concerns;',
            'trait FooTrait',
        ], 'app/Concerns/FooTrait.php');

        $files->deleteDirectory($traitsFolderPath);
    }
}
