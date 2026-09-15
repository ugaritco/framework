<?php

namespace Heritage\Tests\Integration\Generators;

class ConsoleMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Console/Commands/FooCommand.php',
    ];

    public function testItCanGenerateConsoleFile()
    {
        $this->scribe('make:command', ['name' => 'FooCommand'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Console\Commands;',
            'use Heritage\Console\Attributes\Description;',
            'use Heritage\Console\Attributes\Signature;',
            'use Heritage\Console\Command;',
            "#[Signature('app:foo-command')]",
            "#[Description('Command description')]",
            'class FooCommand extends Command',
        ], 'app/Console/Commands/FooCommand.php');
    }

    public function testItCanGenerateConsoleFileWithCommandOption()
    {
        $this->scribe('make:command', ['name' => 'FooCommand', '--command' => 'foo:bar'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Console\Commands;',
            'use Heritage\Console\Attributes\Description;',
            'use Heritage\Console\Attributes\Signature;',
            'use Heritage\Console\Command;',
            "#[Signature('foo:bar')]",
            "#[Description('Command description')]",
            'class FooCommand extends Command',
        ], 'app/Console/Commands/FooCommand.php');
    }
}
