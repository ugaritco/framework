<?php

namespace Heritage\Tests\Integration\Generators;

class CastMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Casts/Foo.php',
    ];

    public function testItCanGenerateCastFile()
    {
        $this->scribe('make:cast', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Casts;',
            'use Heritage\Contracts\Database\Eloquent\CastsAttributes;',
            'class Foo implements CastsAttributes',
            'public function get(Model $model, string $key, mixed $value, array $attributes): mixed',
            'public function set(Model $model, string $key, mixed $value, array $attributes): mixed',
        ], 'app/Casts/Foo.php');
    }

    public function testItCanGenerateInboundCastFile()
    {
        $this->scribe('make:cast', ['name' => 'Foo', '--inbound' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Casts;',
            'use Heritage\Contracts\Database\Eloquent\CastsInboundAttributes;',
            'class Foo implements CastsInboundAttributes',
            'public function set(Model $model, string $key, mixed $value, array $attributes): mixed',
        ], 'app/Casts/Foo.php');
    }
}
