<?php

namespace Heritage\Tests\Integration\Generators;

class JobMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Jobs/FooCreated.php',
        'tests/Feature/Jobs/FooCreatedTest.php',
    ];

    public function testItCanGenerateJobFile()
    {
        $this->scribe('make:job', ['name' => 'FooCreated'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Jobs;',
            'use Heritage\Contracts\Queue\ShouldQueue;',
            'use Heritage\Foundation\Queue\Queueable;',
            'class FooCreated implements ShouldQueue',
            'use Queueable;',
        ], 'app/Jobs/FooCreated.php');

        $this->assertFilenameNotExists('tests/Feature/Jobs/FooCreatedTest.php');
    }

    public function testItCanGenerateSyncJobFile()
    {
        $this->scribe('make:job', ['name' => 'FooCreated', '--sync' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Jobs;',
            'use Heritage\Foundation\Bus\Dispatchable;',
            'class FooCreated',
            'use Dispatchable;',
        ], 'app/Jobs/FooCreated.php');

        $this->assertFileNotContains([
            'use Heritage\Contracts\Queue\ShouldQueue;',
            'use Heritage\Foundation\Queue\Queueable;',
            'use Heritage\Queue\InteractsWithQueue;',
            'use Heritage\Queue\SerializesModels;',
        ], 'app/Jobs/FooCreated.php');
    }

    public function testItCanGenerateJobFileWithTest()
    {
        $this->scribe('make:job', ['name' => 'FooCreated', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Jobs/FooCreated.php');
        $this->assertFilenameExists('tests/Feature/Jobs/FooCreatedTest.php');
    }
}
