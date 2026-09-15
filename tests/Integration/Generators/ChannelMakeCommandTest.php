<?php

namespace Heritage\Tests\Integration\Generators;

class ChannelMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Broadcasting/FooChannel.php',
    ];

    public function testItCanGenerateChannelFile()
    {
        $this->scribe('make:channel', ['name' => 'FooChannel'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Broadcasting;',
            'use Heritage\Foundation\Auth\User;',
            'class FooChannel',
        ], 'app/Broadcasting/FooChannel.php');
    }
}
