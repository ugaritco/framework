<?php

namespace Heritage\Foundation\Queue;

use Heritage\Bus\Queueable as QueueableByBus;
use Heritage\Foundation\Bus\Dispatchable;
use Heritage\Queue\InteractsWithQueue;
use Heritage\Queue\SerializesModels;

trait Queueable
{
    use Dispatchable, InteractsWithQueue, QueueableByBus, SerializesModels;
}
