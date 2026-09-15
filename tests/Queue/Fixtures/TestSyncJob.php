<?php

namespace Heritage\Tests\Queue\Fixtures;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Bus\Dispatchable;

class TestSyncJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        //
    }
}
