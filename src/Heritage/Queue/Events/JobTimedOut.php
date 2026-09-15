<?php

namespace Heritage\Queue\Events;

class JobTimedOut
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  \Heritage\Contracts\Queue\Job  $job  The job instance.
     * @param  int|null  $timeout  The timeout exceeded in seconds.
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $timeout = null,
    ) {
    }
}
