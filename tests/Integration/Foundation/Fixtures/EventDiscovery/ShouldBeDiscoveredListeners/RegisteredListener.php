<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\ShouldBeDiscoveredListeners;

use Heritage\Contracts\Events\ShouldBeDiscovered;
use Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

class RegisteredListener implements ShouldBeDiscovered
{
    public static function shouldBeDiscovered(): bool
    {
        return true;
    }

    public function handle(EventOne $event)
    {
        //
    }
}
