<?php

namespace Heritage\Tests\Integration\Generators;

class RequestMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Http/Requests/FooRequest.php',
    ];

    public function testItCanGenerateRequestFile()
    {
        $this->scribe('make:request', ['name' => 'FooRequest'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Http\Requests;',
            'use Heritage\Foundation\Http\FormRequest;',
            'class FooRequest extends FormRequest',
        ], 'app/Http/Requests/FooRequest.php');
    }
}
