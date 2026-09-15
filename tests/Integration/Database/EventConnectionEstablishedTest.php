<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Database\Events\ConnectionEstablished;
use Heritage\Foundation\Testing\DatabaseMigrations;
use Heritage\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\scribe;

class EventConnectionEstablishedTest extends TestCase
{
    use DatabaseMigrations;

    #[WithMigration]
    public function testItListenToEstablishedConnectionOnReconnect()
    {
        Event::fake([ConnectionEstablished::class]);

        Event::assertNotDispatched(ConnectionEstablished::class);

        scribe($this, 'migrate:fresh');

        Event::assertDispatched(ConnectionEstablished::class);
    }
}
