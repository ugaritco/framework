<?php

namespace Heritage\Tests\Queue\Fixtures;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Bus\Dispatchable;
use Heritage\Queue\InteractsWithQueue;
use Heritage\Support\Carbon;
use Heritage\Support\Facades\DB;

class EloquentTransactionWithAfterCommitTestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $email)
    {
        // ...
    }

    public function handle(): void
    {
        DB::transaction(function () {
            DB::table('password_reset_tokens')->insert([
                ['email' => $this->email, 'token' => sha1($this->email), 'created_at' => Carbon::now()],
            ]);
        });
    }
}
