<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners;

use Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

interface ListenerInterface
{
    public function handle(EventOne $event);
}
