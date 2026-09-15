<?php

namespace Heritage\Tests\Queue\Fixtures;

use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Queue\Queueable;

class FakeSqsJobWithMessageGroup implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }

    /**
     * Message group method called by SqsQueue.
     *
     * @return string
     */
    public function messageGroup(): string
    {
        return 'group-1';
    }
}
