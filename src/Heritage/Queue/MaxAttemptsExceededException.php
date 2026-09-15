<?php

namespace Heritage\Queue;

use RuntimeException;

class MaxAttemptsExceededException extends RuntimeException
{
    /**
     * The job instance.
     *
     * @var \Heritage\Contracts\Queue\Job|null
     */
    public $job;

    /**
     * Create a new instance for the job.
     *
     * @param  \Heritage\Contracts\Queue\Job  $job
     * @return static
     */
    public static function forJob($job)
    {
        return tap(new static($job->resolveName().' has been attempted too many times.'), function ($e) use ($job) {
            $e->job = $job;
        });
    }
}
