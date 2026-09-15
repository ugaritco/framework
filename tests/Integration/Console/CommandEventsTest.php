<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Console\Events\CommandFinished;
use Heritage\Console\Events\CommandStarting;
use Heritage\Contracts\Console\Kernel as ConsoleKernel;
use Heritage\Events\Dispatcher;
use Heritage\Filesystem\Filesystem;
use Heritage\Foundation\Testing\WithConsoleEvents;
use Heritage\Support\Facades\Event;
use Heritage\Support\Str;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CommandEventsTest extends TestCase
{
    use WithConsoleEvents;

    /**
     * The path to the file that execution logs will be written to.
     *
     * @var string
     */
    protected $logfile;

    /**
     * The Filesystem instance for writing stubs and logs.
     *
     * @var \Heritage\Filesystem\Filesystem
     */
    protected $files;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->files = new Filesystem;
            $this->logfile = storage_path(sprintf('logs/command_events_test_%s.log', (string) Str::random()));
        });

        $this->beforeApplicationDestroyed(function () {
            $this->files->delete($this->logfile);

            unset($this->files, $this->logfile);
        });

        parent::setUp();
    }

    #[DataProvider('foregroundCommandEventsProvider')]
    public function testCommandEventsReceiveParsedInput($callback): void
    {
        $this->app[ConsoleKernel::class]->registerCommand(new CommandEventsTestCommand);
        $this->app[Dispatcher::class]->listen(function (CommandStarting $event) {
            array_map(fn ($e) => $this->files->append($this->logfile, $e.PHP_EOL), [
                'CommandStarting',
                $event->input->getArgument('firstname'),
                $event->input->getArgument('lastname'),
                $event->input->getOption('occupation'),
            ]);
        });

        Event::listen(function (CommandFinished $event) {
            array_map(fn ($e) => $this->files->append($this->logfile, $e.PHP_EOL), [
                'CommandFinished',
                $event->input->getArgument('firstname'),
                $event->input->getArgument('lastname'),
                $event->input->getOption('occupation'),
            ]);
        });

        value($callback, $this);

        $this->assertLogged(
            'CommandStarting', 'taylor', 'otwell', 'coding',
            'CommandFinished', 'taylor', 'otwell', 'coding',
        );
    }

    public static function foregroundCommandEventsProvider()
    {
        yield 'Foreground with array' => [function ($testCase) {
            $testCase->scribe(CommandEventsTestCommand::class, [
                'firstname' => 'taylor',
                'lastname' => 'otwell',
                '--occupation' => 'coding',
            ]);
        }];

        yield 'Foreground with string' => [function ($testCase) {
            $testCase->scribe('command-events-test-command taylor otwell --occupation=coding');
        }];
    }

    public function testCommandEventsReceiveParsedInputFromBackground(): void
    {
        $ugarit = Testbench::create(
            basePath: static::applicationBasePath(),
            resolvingCallback: function ($app) {
                $files = new Filesystem;
                $log = fn ($msg) => $files->append($this->logfile, $msg.PHP_EOL);

                $app['events']->listen(function (CommandStarting $event) use ($log) {
                    array_map(fn ($msg) => $log($msg), [
                        'CommandStarting',
                        $event->input->getArgument('firstname'),
                        $event->input->getArgument('lastname'),
                        $event->input->getOption('occupation'),
                    ]);
                });

                $app['events']->listen(function (CommandFinished $event) use ($log) {
                    array_map(fn ($msg) => $log($msg), [
                        'CommandFinished',
                        $event->input->getArgument('firstname'),
                        $event->input->getArgument('lastname'),
                        $event->input->getOption('occupation'),
                    ]);
                });
            },
        );

        tap($ugarit[ConsoleKernel::class], function ($kernel) {
            $kernel->rerouteSymfonyCommandEvents();
            $kernel->registerCommand(new CommandEventsTestCommand);

            $kernel->call(CommandEventsTestCommand::class, [
                'firstname' => 'taylor',
                'lastname' => 'otwell',
                '--occupation' => 'coding',
            ]);
        });

        $this->assertLogged(
            'CommandStarting', 'taylor', 'otwell', 'coding',
            'CommandFinished', 'taylor', 'otwell', 'coding',
        );

        $ugarit->terminate();
    }

    protected function assertLogged(...$messages)
    {
        $log = trim($this->files->get($this->logfile));

        $this->assertEquals(implode(PHP_EOL, $messages), $log);
    }
}

class CommandEventsTestCommand extends \Heritage\Console\Command
{
    protected $signature = 'command-events-test-command {firstname} {lastname} {--occupation=cook}';

    public function handle()
    {
        // ...
    }
}
