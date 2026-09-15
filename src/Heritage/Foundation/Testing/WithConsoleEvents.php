<?php

namespace Heritage\Foundation\Testing;

use Heritage\Contracts\Console\Kernel as ConsoleKernel;

trait WithConsoleEvents
{
    /**
     * Register console events.
     *
     * @return void
     */
    protected function setUpWithConsoleEvents()
    {
        $this->app[ConsoleKernel::class]->rerouteSymfonyCommandEvents();
    }
}
