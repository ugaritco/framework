<?php

namespace Heritage\Bus\Events;

use Heritage\Bus\Batch;

class BatchDispatched
{
    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Bus\Batch  $batch  The batch instance.
     */
    public function __construct(
        public Batch $batch,
    ) {
    }
}
