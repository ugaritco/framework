<?php

namespace Heritage\Queue\Events;

class WorkerStarting
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Heritage\Queue\WorkerOptions  $workerOptions
     */
    public function __construct(
        public $connectionName,
        public $queue,
        public $workerOptions,
    ) {
    }
}
