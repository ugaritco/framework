<?php

namespace Heritage\Tests\Integration\Generators;

class ResourceMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Http/Resources/FooResource.php',
        'app/Http/Resources/FooResourceCollection.php',
    ];

    public function testItCanGenerateResourceFile()
    {
        $this->scribe('make:resource', ['name' => 'FooResource'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Http\Resources;',
            'use Heritage\Http\Resources\Json\JsonResource;',
            'class FooResource extends JsonResource',
        ], 'app/Http/Resources/FooResource.php');
    }

    public function testItCanGenerateResourceCollectionFile()
    {
        $this->scribe('make:resource', ['name' => 'FooResourceCollection', '--collection' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Http\Resources;',
            'use Heritage\Http\Resources\Json\ResourceCollection;',
            'class FooResourceCollection extends ResourceCollection',
        ], 'app/Http/Resources/FooResourceCollection.php');
    }

    public function testItCanGenerateJsonApiResourceFile()
    {
        $this->scribe('make:resource', ['name' => 'FooResource', '--json-api' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Http\Resources;',
            'use Heritage\Http\Resources\JsonApi\JsonApiResource;',
            'class FooResource extends JsonApiResource',
        ], 'app/Http/Resources/FooResource.php');

        $this->assertFileNotContains([
            'use Heritage\Http\Request;',
        ], 'app/Http/Resources/FooResource.php');
    }
}
