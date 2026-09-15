<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners;

use Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

abstract class AbstractListener
{
    abstract public function handle(EventOne $event);
}
