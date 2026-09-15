<?php

namespace Heritage\Console\Events;

use Heritage\Console\Scheduling\Event;
use Throwable;

class ScheduledTaskFailed
{
    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Console\Scheduling\Event  $task  The scheduled event that failed.
     * @param  \Throwable  $exception  The exception that was thrown.
     */
    public function __construct(
        public Event $task,
        public Throwable $exception,
    ) {
    }
}
