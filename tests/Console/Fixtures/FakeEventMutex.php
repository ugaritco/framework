<?php

namespace Heritage\Tests\Console\Fixtures;

use Heritage\Console\Scheduling\Event;
use Heritage\Console\Scheduling\EventMutex;

class FakeEventMutex implements EventMutex
{
    public function create(Event $event)
    {
    }

    public function exists(Event $event)
    {
    }

    public function forget(Event $event)
    {
    }
}
