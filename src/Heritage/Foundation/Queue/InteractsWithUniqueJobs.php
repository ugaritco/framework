<?php

namespace Heritage\Foundation\Queue;

use Heritage\Bus\Queueable;
use Heritage\Bus\UniqueLock;
use Heritage\Contracts\Queue\ShouldBeUnique;
use Heritage\Support\Facades\Context;

trait InteractsWithUniqueJobs
{
    /**
     * Store unique job information in the context in case we can't resolve the job on the queue side.
     *
     * @param  mixed  $job
     * @return void
     */
    public function addUniqueJobInformationToContext($job): void
    {
        if ($job instanceof ShouldBeUnique) {
            Context::addHidden([
                'ugarit_unique_job_cache_store' => $this->getUniqueJobCacheStore($job),
                'ugarit_unique_job_key' => UniqueLock::getKey($job),
                'ugarit_unique_job_lock_owner' => isset(class_uses_recursive($job)[Queueable::class])
                    ? ($job->uniqueLockOwner ?? '')
                    : '',
            ]);
        }
    }

    /**
     * Remove the unique job information from the context.
     *
     * @param  mixed  $job
     * @return void
     */
    public function removeUniqueJobInformationFromContext($job): void
    {
        if ($job instanceof ShouldBeUnique) {
            Context::forgetHidden([
                'ugarit_unique_job_cache_store',
                'ugarit_unique_job_key',
                'ugarit_unique_job_lock_owner',
            ]);
        }
    }

    /**
     * Determine the cache store used by the unique job to acquire locks.
     *
     * @param  mixed  $job
     * @return string|null
     */
    protected function getUniqueJobCacheStore($job): ?string
    {
        return method_exists($job, 'uniqueVia')
            ? $job->uniqueVia()->getName()
            : config('cache.default');
    }
}
