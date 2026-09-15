<?php

namespace Heritage\Support\Testing\Fakes;

use Closure;

class ChainedBatchTruthTest
{
    /**
     * The underlying truth test.
     *
     * @var \Closure(\Heritage\Bus\PendingBatch): bool
     */
    protected $callback;

    /**
     * Create a new truth test instance.
     *
     * @param  \Closure(\Heritage\Bus\PendingBatch): bool  $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Invoke the truth test with the given pending batch.
     *
     * @param  \Heritage\Bus\PendingBatch  $pendingBatch
     * @return bool
     */
    public function __invoke($pendingBatch)
    {
        return call_user_func($this->callback, $pendingBatch);
    }
}
