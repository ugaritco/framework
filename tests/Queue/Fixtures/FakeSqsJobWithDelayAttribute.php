<?php

namespace Heritage\Tests\Queue\Fixtures;

use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Queue\Queueable;
use Heritage\Queue\Attributes\Delay;

#[Delay(15)]
class FakeSqsJobWithDelayAttribute implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}
