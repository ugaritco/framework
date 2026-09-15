<?php

namespace Heritage\Mail;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Mail\Factory as MailFactory;
use Heritage\Contracts\Mail\Mailable as MailableContract;
use Heritage\Contracts\Queue\ShouldBeEncrypted;
use Heritage\Contracts\Queue\ShouldQueueAfterCommit;
use Heritage\Queue\Attributes\Backoff;
use Heritage\Queue\Attributes\Connection;
use Heritage\Queue\Attributes\MaxExceptions;
use Heritage\Queue\Attributes\Queue as QueueAttribute;
use Heritage\Queue\Attributes\ReadsQueueAttributes;
use Heritage\Queue\Attributes\Timeout;
use Heritage\Queue\Attributes\Tries;
use Heritage\Queue\InteractsWithQueue;

class SendQueuedMailable
{
    use InteractsWithQueue, Queueable, ReadsQueueAttributes;

    /**
     * The mailable message instance.
     *
     * @var \Heritage\Contracts\Mail\Mailable
     */
    public $mailable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int|null
     */
    public $maxExceptions;

    /**
     * Indicates if the job should be encrypted.
     *
     * @var bool
     */
    public $shouldBeEncrypted = false;

    /**
     * Create a new job instance.
     *
     * @param  \Heritage\Contracts\Mail\Mailable  $mailable
     */
    public function __construct(MailableContract $mailable)
    {
        $this->mailable = $mailable;

        if ($mailable instanceof ShouldQueueAfterCommit) {
            $this->afterCommit = true;
        } else {
            $this->afterCommit = property_exists($mailable, 'afterCommit') ? $mailable->afterCommit : null;
        }

        $this->connection = $this->getAttributeValue($mailable, Connection::class, 'connection');
        $this->maxExceptions = $this->getAttributeValue($mailable, MaxExceptions::class, 'maxExceptions');
        $this->queue = $this->getAttributeValue($mailable, QueueAttribute::class, 'queue');
        $this->shouldBeEncrypted = $mailable instanceof ShouldBeEncrypted;
        $this->timeout = $this->getAttributeValue($mailable, Timeout::class, 'timeout');
        $this->tries = $this->getAttributeValue($mailable, Tries::class, 'tries');
    }

    /**
     * Handle the queued job.
     *
     * @param  \Heritage\Contracts\Mail\Factory  $factory
     * @return void
     */
    public function handle(MailFactory $factory)
    {
        $this->mailable->send($factory);
    }

    /**
     * Get the number of seconds before a released mailable will be available.
     *
     * @return mixed
     */
    public function backoff()
    {
        $backoff = $this->getAttributeValue($this->mailable, Backoff::class, 'backoff');

        if (method_exists($this->mailable, 'backoff')) {
            $backoff = $this->mailable->backoff();
        }

        return $backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime|null
     */
    public function retryUntil()
    {
        if (! method_exists($this->mailable, 'retryUntil') && ! isset($this->mailable->retryUntil)) {
            return;
        }

        return $this->mailable->retryUntil ?? $this->mailable->retryUntil();
    }

    /**
     * Call the failed method on the mailable instance.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e)
    {
        if (method_exists($this->mailable, 'failed')) {
            $this->mailable->failed($e);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->mailable);
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->mailable = clone $this->mailable;
    }
}
