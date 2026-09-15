<?php

namespace Heritage\Tests\Integration\Database\Queue\Fixtures;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Queue\InteractsWithQueue;
use Heritage\Support\Facades\DB;

class TimeOutNonBatchableJobWithTransaction implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 2;

    public function handle(): void
    {
        DB::transaction(fn () => sleep(20));
    }
}
