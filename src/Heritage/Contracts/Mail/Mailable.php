<?php

namespace Heritage\Contracts\Mail;

use Heritage\Contracts\Queue\Factory as Queue;

interface Mailable
{
    /**
     * Send the message using the given mailer.
     *
     * @param  \Heritage\Contracts\Mail\Factory|\Heritage\Contracts\Mail\Mailer  $mailer
     * @return \Heritage\Mail\SentMessage|null
     */
    public function send($mailer);

    /**
     * Queue the given message.
     *
     * @param  \Heritage\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function queue(Queue $queue);

    /**
     * Deliver the queued message after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Heritage\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function later($delay, Queue $queue);

    /**
     * Set the recipients of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function cc($address, $name = null);

    /**
     * Set the recipients of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function bcc($address, $name = null);

    /**
     * Set the recipients of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function to($address, $name = null);

    /**
     * Set the locale of the message.
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale);

    /**
     * Set the name of the mailer that should be used to send the message.
     *
     * @param  string  $mailer
     * @return $this
     */
    public function mailer($mailer);
}
