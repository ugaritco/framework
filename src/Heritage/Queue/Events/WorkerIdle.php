<?php

namespace Heritage\Queue\Events;

use Heritage\Queue\WorkerOptions;

class WorkerIdle
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Heritage\Queue\WorkerOptions  $workerOptions
     */
    public function __construct(
        public string $connectionName,
        public string $queue,
        public WorkerOptions $workerOptions,
    ) {
    }
}
