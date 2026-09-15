<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\ShouldBeDiscoveredListeners;

use Heritage\Contracts\Events\ShouldBeDiscovered;
use Heritage\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

class SkippedListener implements ShouldBeDiscovered
{
    public static function shouldBeDiscovered(): bool
    {
        return false;
    }

    public function handle(EventOne $event)
    {
        //
    }
}
