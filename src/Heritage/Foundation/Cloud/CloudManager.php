<?php

namespace Heritage\Foundation\Cloud;

use Heritage\Contracts\Container\Container;
use Heritage\Support\Traits\Macroable;
use RuntimeException;

class CloudManager
{
    use Macroable;

    /**
     * Create a new Ugarit Cloud manager instance.
     */
    public function __construct(protected Container $container)
    {
    }

    /**
     * Determine if the application is currently hosted on Ugarit Cloud.
     */
    public function hosted(): bool
    {
        return ugarit_cloud();
    }

    /**
     * Determine if the application is using Ugarit Cloud managed queues.
     */
    public function usesManagedQueues(): bool
    {
        return $this->container->make('config')->get('queue.connections.cloud.driver') === 'cloud';
    }

    /**
     * Get the Ugarit Cloud managed queue connection.
     *
     * @throws \RuntimeException
     */
    public function queue(): Queue
    {
        if (! $this->usesManagedQueues()) {
            throw new RuntimeException('Ugarit Cloud managed queues are not configured for this application.');
        }

        return $this->container->make('queue')->connection('cloud');
    }

    /**
     * Determine if the given queue is managed by Ugarit Cloud.
     */
    public function isManagedQueue(string $queue): bool
    {
        return $this->usesManagedQueues() && in_array($queue, $this->queue()->managedQueues(), true);
    }
}
