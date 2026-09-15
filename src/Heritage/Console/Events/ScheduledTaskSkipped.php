<?php

namespace Heritage\Console\Events;

use Heritage\Console\Scheduling\Event;

class ScheduledTaskSkipped
{
    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Console\Scheduling\Event  $task  The scheduled event being run.
     */
    public function __construct(
        public Event $task,
    ) {
    }
}
