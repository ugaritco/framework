<?php

namespace Heritage\Tests\Integration\Queue\Fixtures\Jobs;

use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Auth\User;
use Heritage\Foundation\Queue\Queueable;

class DeleteUser implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {
        log($user);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->delete();
    }
}
