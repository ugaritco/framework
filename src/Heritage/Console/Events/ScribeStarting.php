<?php

namespace Heritage\Console\Events;

use Heritage\Console\Application;

class ScribeStarting
{
    /**
     * Create a new event instance.
     *
     * @param  \Heritage\Console\Application  $scribe  The Scribe application instance.
     */
    public function __construct(
        public Application $scribe,
    ) {
    }
}
