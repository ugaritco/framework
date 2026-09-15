<?php

namespace Heritage\Contracts\Broadcasting;

interface ShouldBroadcast
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Heritage\Broadcasting\Channel|\Heritage\Broadcasting\Channel[]|string[]|string
     */
    public function broadcastOn();
}
