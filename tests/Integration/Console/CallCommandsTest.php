<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Foundation\Console\ViewClearCommand;
use Heritage\Support\Facades\Scribe;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class CallCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Scribe::command('test:a', function () {
                $this->call('view:clear');
            });

            Scribe::command('test:b', function () {
                $this->call(ViewClearCommand::class);
            });

            Scribe::command('test:c', function () {
                $this->call($this->ugarit->make(ViewClearCommand::class));
            });
        });

        parent::setUp();
    }

    #[TestWith(['test:a'])]
    #[TestWith(['test:b'])]
    #[TestWith(['test:c'])]
    public function testItCanCallCommands(string $command): void
    {
        $this->scribe($command)->assertSuccessful();
    }
}
