<?php

namespace Heritage\Tests\Console;

use Composer\Autoload\ClassLoader;
use Heritage\Console\Application;
use Heritage\Console\Command;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Contracts\Foundation\Application as ApplicationContract;
use Heritage\Events\Dispatcher as EventsDispatcher;
use Heritage\Filesystem\Filesystem;
use Heritage\Foundation\Application as FoundationApplication;
use Heritage\Foundation\Console\Kernel;
use Heritage\Tests\Console\Fixtures\FakeCommandWithArrayInputPrompting;
use Heritage\Tests\Console\Fixtures\FakeCommandWithInputPrompting;
use Mockery;
use Orchestra\Testbench\Concerns\InteractsWithMockery;
use Orchestra\Testbench\Foundation\Application as Testbench;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Throwable;

use function Heritage\Filesystem\join_paths;
use function Orchestra\Testbench\default_skeleton_path;

class ConsoleApplicationTest extends TestCase
{
    use InteractsWithMockery;

    protected function tearDown(): void
    {
        $this->tearDownTheTestEnvironmentUsingMockery();
    }

    public function testAddSetsUgaritInstance()
    {
        $scribe = $this->getMockConsole(['addToParent']);
        $command = Mockery::mock(Command::class);
        $command->expects('setUgarit')->with(Mockery::type(ApplicationContract::class));
        $scribe->expects($this->once())->method('addToParent')->with($command)->willReturn($command);
        $result = $scribe->add($command);

        $this->assertSame($command, $result);
    }

    public function testUgaritNotSetOnSymfonyCommands()
    {
        $scribe = $this->getMockConsole(['addToParent']);
        $command = Mockery::mock(SymfonyCommand::class);
        $command->shouldReceive('setUgarit')->never();
        $scribe->expects($this->once())->method('addToParent')->with($command)->willReturn($command);
        $result = $scribe->add($command);

        $this->assertSame($command, $result);
    }

    public function testResolveAddsCommandViaApplicationResolution()
    {
        $scribe = $this->getMockConsole(['addToParent']);
        $command = Mockery::mock(SymfonyCommand::class);
        $scribe->getUgarit()->expects('make')->with('foo')->andReturn(Mockery::mock(SymfonyCommand::class));
        $scribe->expects($this->once())->method('addToParent')->with($command)->willReturn($command);
        $result = $scribe->resolve('foo');

        $this->assertSame($command, $result);
    }

    public function testResolvingCommandsWithAliasViaAttribute()
    {
        $container = new FoundationApplication();
        $scribe = new Application($container, new EventsDispatcher($container), $container->version());
        $scribe->resolve(CommandWithAliasViaAttribute::class);
        $scribe->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithAliasViaAttribute::class, $scribe->get('command-name'));
        $this->assertInstanceOf(CommandWithAliasViaAttribute::class, $scribe->get('command-alias'));
        $this->assertArrayHasKey('command-name', $scribe->all());
        $this->assertArrayHasKey('command-alias', $scribe->all());
    }

    public function testResolvingCommandsWithAliasViaProperty()
    {
        $container = new FoundationApplication();
        $scribe = new Application($container, new EventsDispatcher($container), $container->version());
        $scribe->resolve(CommandWithAliasViaProperty::class);
        $scribe->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithAliasViaProperty::class, $scribe->get('command-name'));
        $this->assertInstanceOf(CommandWithAliasViaProperty::class, $scribe->get('command-alias'));
        $this->assertArrayHasKey('command-name', $scribe->all());
        $this->assertArrayHasKey('command-alias', $scribe->all());
    }

    public function testResolvingCommandsWithNoAliasViaAttribute()
    {
        $container = new FoundationApplication();
        $scribe = new Application($container, new EventsDispatcher($container), $container->version());
        $scribe->resolve(CommandWithNoAliasViaAttribute::class);
        $scribe->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithNoAliasViaAttribute::class, $scribe->get('command-name'));
        try {
            $scribe->get('command-alias');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(CommandNotFoundException::class, $e);
        }
        $this->assertArrayHasKey('command-name', $scribe->all());
        $this->assertArrayNotHasKey('command-alias', $scribe->all());
    }

    public function testResolvingCommandsWithNoAliasViaProperty()
    {
        $container = new FoundationApplication();
        $scribe = new Application($container, new EventsDispatcher($container), $container->version());
        $scribe->resolve(CommandWithNoAliasViaProperty::class);
        $scribe->setContainerCommandLoader();

        $this->assertInstanceOf(CommandWithNoAliasViaProperty::class, $scribe->get('command-name'));
        try {
            $scribe->get('command-alias');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertInstanceOf(CommandNotFoundException::class, $e);
        }
        $this->assertArrayHasKey('command-name', $scribe->all());
        $this->assertArrayNotHasKey('command-alias', $scribe->all());
    }

    public function testCallFullyStringCommandLine()
    {
        $scribe = new Application(
            $app = Mockery::mock(ApplicationContract::class, ['version' => '6.0']),
            new EventsDispatcher($app),
            'testing'
        );

        $codeOfCallingArrayInput = $scribe->call('help', [
            '--raw' => true,
            '--format' => 'txt',
            '--no-interaction' => true,
            '--env' => 'testing',
        ]);

        $outputOfCallingArrayInput = $scribe->output();

        $codeOfCallingStringInput = $scribe->call(
            'help --raw --format=txt --no-interaction --env=testing'
        );

        $outputOfCallingStringInput = $scribe->output();

        $this->assertSame($codeOfCallingArrayInput, $codeOfCallingStringInput);
        $this->assertSame($outputOfCallingArrayInput, $outputOfCallingStringInput);
    }

    public function testCommandInputPromptsWhenRequiredArgumentIsMissing()
    {
        $scribe = new Application(
            $ugarit = new FoundationApplication(__DIR__),
            new EventsDispatcher($ugarit),
            'testing'
        );

        $scribe->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $command->setUgarit($ugarit);

        $exitCode = $scribe->call('fake-command-for-testing');

        $this->assertTrue($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $exitCode);
    }

    public function testCommandInputDoesntPromptWhenRequiredArgumentIsPassed()
    {
        $scribe = new Application(
            $ugarit = new FoundationApplication(__DIR__),
            new EventsDispatcher($ugarit),
            'testing'
        );

        $scribe->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $exitCode = $scribe->call('fake-command-for-testing', [
            'name' => 'foo',
        ]);

        $this->assertFalse($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $exitCode);
    }

    public function testCommandInputPromptsWhenRequiredArgumentsAreMissing()
    {
        $scribe = new Application(
            $ugarit = new FoundationApplication(__DIR__),
            new EventsDispatcher($ugarit),
            'testing'
        );

        $scribe->addCommands([$command = new FakeCommandWithArrayInputPrompting()]);

        $command->setUgarit($ugarit);

        $exitCode = $scribe->call('fake-command-for-testing-array');

        $this->assertTrue($command->prompted);
        $this->assertSame(['foo'], $command->argument('names'));
        $this->assertSame(0, $exitCode);
    }

    public function testCommandInputDoesntPromptWhenRequiredArgumentsArePassed()
    {
        $scribe = new Application(
            $ugarit = new FoundationApplication(__DIR__),
            new EventsDispatcher($ugarit),
            'testing'
        );

        $scribe->addCommands([$command = new FakeCommandWithArrayInputPrompting()]);

        $exitCode = $scribe->call('fake-command-for-testing-array', [
            'names' => ['foo', 'bar', 'baz'],
        ]);

        $this->assertFalse($command->prompted);
        $this->assertSame(['foo', 'bar', 'baz'], $command->argument('names'));
        $this->assertSame(0, $exitCode);
    }

    public function testCallMethodCanCallScribeCommandUsingCommandClassObject()
    {
        $scribe = new Application(
            $ugarit = new FoundationApplication(__DIR__),
            new EventsDispatcher($ugarit),
            'testing'
        );

        $scribe->addCommands([$command = new FakeCommandWithInputPrompting()]);

        $command->setUgarit($ugarit);

        $exitCode = $scribe->call($command);

        $this->assertTrue($command->prompted);
        $this->assertSame('foo', $command->argument('name'));
        $this->assertSame(0, $exitCode);
    }

    #[RunInSeparateProcess]
    public function testLoadIgnoresTestFiles()
    {
        $files = new Filesystem;

        $files->ensureDirectoryExists(join_paths(default_skeleton_path(), 'app', 'Console', 'Commands'), 0755, true);

        try {
            $files->put(
                join_paths(default_skeleton_path(), 'app', 'Console', 'Commands', 'ExampleCommand.php'),
                '<?php namespace App\Console\Commands; class ExampleCommand extends \Heritage\Console\Command { protected $signature = "example"; public function handle() {} }'
            );

            $files->put(
                join_paths(default_skeleton_path(), 'app', 'Console', 'Commands', 'ExampleCommandTest.php'),
                '<?php namespace App\Console\Commands; class ExampleCommandTest extends \Heritage\Console\Command { protected $signature = "example-test"; public function handle() {} }'
            );

            $files->put(
                join_paths(default_skeleton_path(), 'app', 'Console', 'Commands', 'ExampleCommandUnitTest.php'),
                '<?php namespace App\Console\Commands; class ExampleCommandUnitTest extends \PHPUnit\Framework\TestCase { public function test_command() { $this->assertTrue(true); } }'
            );

            foreach (ClassLoader::getRegisteredLoaders() as $loader) {
                $loader->addPsr4('App\\', [default_skeleton_path('app')]);
            }

            $app = Testbench::create(default_skeleton_path());
            $events = new EventsDispatcher($app);
            $app->instance('events', $events);

            $kernel = new TestKernel($app, $events);

            $commands = $kernel->getRegisteredCommands();

            $this->assertContains('App\Console\Commands\ExampleCommand', $commands);
            $this->assertContains('App\Console\Commands\ExampleCommandTest', $commands);
            $this->assertNotContains('App\Console\Commands\ExampleCommandUnitTest', $commands);

            Testbench::flushState($this);
        } finally {
            $files->cleanDirectory(default_skeleton_path('app', 'Console', 'Commands'));
        }
    }

    protected function getMockConsole(array $methods)
    {
        $app = Mockery::mock(ApplicationContract::class, ['version' => '6.0']);
        $events = Mockery::mock(Dispatcher::class, ['dispatch' => null]);

        return $this->getMockBuilder(Application::class)->onlyMethods($methods)->setConstructorArgs([
            $app, $events, 'test-version',
        ])->getMock();
    }
}

#[AsCommand('command-name')]
class CommandWithNoAliasViaAttribute extends Command
{
    //
}
#[AsCommand('command-name', aliases: ['command-alias'])]
class CommandWithAliasViaAttribute extends Command
{
    //
}

class CommandWithNoAliasViaProperty extends Command
{
    public $name = 'command-name';
}

class CommandWithAliasViaProperty extends Command
{
    public $name = 'command-name';
    public $aliases = ['command-alias'];
}

class TestKernel extends Kernel
{
    public $loadedCommands = [];

    public function loadFrom($paths)
    {
        $this->load($paths);
    }

    #[\Override]
    protected function commandClassFromFile(\SplFileInfo $file, string $namespace): string
    {
        return tap(parent::commandClassFromFile($file, $namespace), fn ($command) => $this->loadedCommands[] = $command);
    }

    public function getRegisteredCommands(): array
    {
        return collect($this->getScribe()->all())->values()->transform(fn ($command) => $command::class)->all();
    }
}
