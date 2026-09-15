<?php

namespace Heritage\Tests\Foundation;

use Heritage\Foundation\Console\DevListCommand;
use Heritage\Foundation\DevCommands;
use Heritage\Support\Facades\Scribe;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class FoundationDevListCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $ref = new ReflectionClass(DevCommands::class);

        foreach (['commands', 'except', 'only'] as $prop) {
            $ref->getProperty($prop)->setValue(null, []);
        }

        $ref->getProperty('colorCount')->setValue(null, 0);
    }

    public function testListsRegisteredCommands()
    {
        DevCommands::register('echo hello', 'greeter');
        DevCommands::register('php scribe serve', 'server');

        $this->scribe('dev:list')
            ->expectsOutputToContain('greeter')
            ->expectsOutputToContain('server')
            ->expectsOutputToContain('Showing [2] dev commands')
            ->assertSuccessful();
    }

    public function testShowsSingularCommandLabel()
    {
        DevCommands::register('echo hello', 'greeter');

        $this->scribe('dev:list')
            ->expectsOutputToContain('Showing [1] dev command ')
            ->assertSuccessful();
    }

    public function testJsonOutputContainsAllFields()
    {
        DevCommands::register('echo hello', 'greeter');

        $this->scribe('dev:list', ['--json' => true])
            ->assertSuccessful();

        $this->scribe('dev:list', ['--json' => true])
            ->expectsOutput(json_encode(array_map(fn ($command) => array_merge($command, [
                'source' => $this->formatSource($command['source']),
            ]), DevCommands::commands())))
            ->assertSuccessful();
    }

    public function testFilterByName()
    {
        DevCommands::register('echo hello', 'greeter');
        DevCommands::register('php scribe serve', 'server');

        $this->scribe('dev:list', ['--filter' => 'server', '--json' => true])
            ->assertSuccessful();
    }

    public function testFilterByCommand()
    {
        DevCommands::register('echo hello', 'greeter');
        DevCommands::register('php scribe serve', 'server');

        $this->scribe('dev:list', ['--filter' => 'scribe', '--json' => true])
            ->assertSuccessful();
    }

    public function testFilterReturnsFailureWhenNoMatch()
    {
        DevCommands::register('echo hello', 'greeter');

        $this->scribe('dev:list', ['--filter' => 'nonexistent', '--json' => true])
            ->assertFailed();
    }

    public function testEmptyStateWithNoCommands()
    {
        $this->scribe('dev:list')
            ->expectsOutputToContain("doesn't have any dev processes")
            ->assertSuccessful();
    }

    public function testEmptyStateWithFilterReturnsFailure()
    {
        DevCommands::register('echo hello', 'greeter');

        $this->scribe('dev:list', ['--filter' => 'nonexistent'])
            ->expectsOutputToContain("doesn't have any dev processes matching the given criteria")
            ->assertFailed();
    }

    public function testExceptVendorExcludesVendorCommands()
    {
        DevCommands::register('echo hello', 'app-cmd');

        $commands = DevCommands::commands();

        $this->scribe('dev:list', ['--except-vendor' => true, '--json' => true])
            ->assertSuccessful();
    }

    public function testOnlyVendorWithNoVendorCommandsReturnsEmpty()
    {
        DevCommands::register('echo hello', 'app-cmd');

        $this->scribe('dev:list', ['--only-vendor' => true, '--json' => true])
            ->assertFailed();
    }

    public function testJsonOutputWithFilterContainsOnlyMatchingCommands()
    {
        DevCommands::register('echo hello', 'greeter');
        DevCommands::register('php scribe serve', 'server');
        DevCommands::register('php scribe queue:listen', 'queue');

        $output = $this->getJsonOutput(['--filter' => 'scribe']);

        $this->assertCount(2, $output);
        $this->assertSame('server', $output[0]['name']);
        $this->assertSame('queue', $output[1]['name']);
    }

    public function testJsonOutputIncludesSource()
    {
        DevCommands::register('echo hello', 'greeter');

        $output = $this->getJsonOutput();

        $this->assertArrayHasKey('source', $output[0]);
        $this->assertStringContainsString(__CLASS__, $output[0]['source']);
    }

    public function testFormatSourceWithClassAndFunction()
    {
        $command = new DevListCommand();

        $ref = new ReflectionClass($command);
        $method = $ref->getMethod('formatSource');

        $result = $method->invoke($command, [
            'file' => '/some/path.php',
            'line' => 42,
            'class' => 'App\\Providers\\AppServiceProvider',
            'function' => 'boot',
        ]);

        $this->assertSame('App\\Providers\\AppServiceProvider@boot', $result);
    }

    public function testFormatSourceWithFileAndLine()
    {
        $command = new DevListCommand();

        $ref = new ReflectionClass($command);
        $method = $ref->getMethod('formatSource');

        $result = $method->invoke($command, [
            'file' => '/app/routes/console.php',
            'line' => 15,
        ]);

        $this->assertSame('/app/routes/console.php:15', $result);
    }

    public function testFormatSourceWithEmptyArray()
    {
        $command = new DevListCommand();

        $ref = new ReflectionClass($command);
        $method = $ref->getMethod('formatSource');

        $result = $method->invoke($command, []);

        $this->assertSame('', $result);
    }

    public function testCombinedFilterAndVendorOptions()
    {
        DevCommands::register('echo hello', 'greeter');
        DevCommands::register('php scribe serve', 'server');

        $output = $this->getJsonOutput(['--filter' => 'server', '--except-vendor' => true]);

        $this->assertCount(1, $output);
        $this->assertSame('server', $output[0]['name']);
    }

    protected function getJsonOutput(array $options = []): array
    {
        $options['--json'] = true;

        Scribe::call('dev:list', $options);

        return json_decode(Scribe::output(), true);
    }

    protected function formatSource(array $source): string
    {
        $class = $source['class'] ?? null;
        $function = $source['function'] ?? null;

        if ($class) {
            return "{$class}@{$function}";
        }

        return implode(':', array_filter([$source['file'] ?? null, $source['line'] ?? null]));
    }
}
