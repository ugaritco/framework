<?php

namespace Heritage\Tests\Integration\Generators;

class EnumMakeCommandTest extends TestCase
{
    protected $files = [
        'app/IntEnum.php',
        'app/StatusEnum.php',
        'app/StringEnum.php',
        'app/*/OrderStatusEnum.php',
    ];

    public function testItCanGenerateEnumFile()
    {
        $this->scribe('make:enum', ['name' => 'StatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'enum StatusEnum',
        ], 'app/StatusEnum.php');
    }

    public function testItCanGenerateEnumFileWithString()
    {
        $this->scribe('make:enum', ['name' => 'StringEnum', '--string' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'enum StringEnum: string',
        ], 'app/StringEnum.php');
    }

    public function testItCanGenerateEnumFileWithInt()
    {
        $this->scribe('make:enum', ['name' => 'IntEnum', '--int' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'enum IntEnum: int',
        ], 'app/IntEnum.php');
    }

    public function testItCanGenerateEnumFileInEnumsFolder()
    {
        $enumsFolderPath = app_path('Enums');

        /** @var \Heritage\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($enumsFolderPath);

        $this->scribe('make:enum', ['name' => 'OrderStatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums;',
            'enum OrderStatusEnum',
        ], 'app/Enums/OrderStatusEnum.php');

        $files->deleteDirectory($enumsFolderPath);
    }

    public function testItCanGenerateEnumFileInEnumerationsFolder()
    {
        $enumerationsFolderPath = app_path('Enumerations');

        /** @var \Heritage\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($enumerationsFolderPath);

        $this->scribe('make:enum', ['name' => 'OrderStatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enumerations;',
            'enum OrderStatusEnum',
        ], 'app/Enumerations/OrderStatusEnum.php');

        $files->deleteDirectory($enumerationsFolderPath);
    }
}
