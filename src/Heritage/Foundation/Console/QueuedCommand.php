<?php

namespace Heritage\Foundation\Console;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Console\Kernel as KernelContract;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Bus\Dispatchable;

class QueuedCommand implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * The data to pass to the Scribe command.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param  array  $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Handle the job.
     *
     * @param  \Heritage\Contracts\Console\Kernel  $kernel
     * @return void
     */
    public function handle(KernelContract $kernel)
    {
        $kernel->call(...array_values($this->data));
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return array_values($this->data)[0];
    }
}
