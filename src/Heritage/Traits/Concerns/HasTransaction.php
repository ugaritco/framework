<?php

declare(strict_types=1);

namespace Heritage\Traits\Concerns;

use Heritage\Support\Facades\DB;
use Throwable;

/**
 * Trait HasTransaction
 *
 * Provides database transaction management for services, features, and workflows.
 */
trait HasTransaction
{
    /**
     * Execute a callable within a database transaction.
     *
     * @param  callable  $callback  The callback to execute inside the transaction.
     * @param  int  $attempts  The number of attempts before failing if deadlock occurs.
     * @return mixed The return value of the callback.
     *
     * @throws Throwable If transaction fails and is rolled back.
     */
    protected function transaction(callable $callback, int $attempts = 1): mixed
    {
        // Execute the given callback within an atomic database transaction
        return DB::transaction($callback, $attempts);
    }
}
