<?php

namespace Heritage\Tests\Queue\Fixtures;

use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Queue\Queueable;

class FakeSqsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}
