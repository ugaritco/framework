<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\UnionListeners;

use Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventTwo;

class UnionListener
{
    public function handle(EventOne|EventTwo $event)
    {
        //
    }
}
