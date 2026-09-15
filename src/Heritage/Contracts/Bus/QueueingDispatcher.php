<?php

namespace Heritage\Contracts\Bus;

interface QueueingDispatcher extends Dispatcher
{
    /**
     * Attempt to find the batch with the given ID.
     *
     * @param  string  $batchId
     * @return \Heritage\Bus\Batch|null
     */
    public function findBatch(string $batchId);

    /**
     * Create a new batch of queueable jobs.
     *
     * @param  \Heritage\Support\Collection|array  $jobs
     * @return \Heritage\Bus\PendingBatch
     */
    public function batch($jobs);

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command);
}
