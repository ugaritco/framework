<?php

namespace Heritage\Tests\Integration\Generators;

class FactoryMakeCommandTest extends TestCase
{
    protected $files = [
        'database/factories/FooFactory.php',
    ];

    public function testItCanGenerateFactoryFile()
    {
        $this->scribe('make:factory', ['name' => 'FooFactory'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Database\Factories;',
            'use Heritage\Database\Eloquent\Factories\Factory;',
            'class FooFactory extends Factory',
            'public function definition()',
        ], 'database/factories/FooFactory.php');

        $this->assertFileNotContains([
            'protected $model',
        ], 'database/factories/FooFactory.php');
    }

    public function testItDeclaresTheModelOnlyWhenItIsNotGuessed()
    {
        $this->scribe('make:factory', ['name' => 'FooFactory', '--model' => 'Bar'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'use App\Models\Bar;',
            'protected $model = Bar::class;',
        ], 'database/factories/FooFactory.php');
    }
}
