<?php

namespace Heritage\Tests\Integration\Database\Queue\Fixtures;

use Heritage\Bus\Batchable;
use Heritage\Bus\Queueable;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Queue\InteractsWithQueue;
use Heritage\Support\Facades\DB;

class TimeOutJobWithNestedTransactions implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Batchable;

    public int $tries = 1;
    public int $timeout = 2;

    public function handle(): void
    {
        DB::transaction(function () {
            DB::transaction(fn () => sleep(20));
        });
    }
}
