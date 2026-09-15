<?php

namespace Heritage\Tests\Integration\Mail;

use Heritage\Mail\Mailable;
use Heritage\Mail\SendQueuedMailable;
use Heritage\Queue\Middleware\RateLimited;
use Heritage\Support\Carbon;
use Heritage\Support\Facades\Mail;
use Heritage\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class SendingQueuedMailTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('mail.driver', 'array');

        $app['view']->addLocation(__DIR__.'/Fixtures');
    }

    public function testMailIsSentWithDefaultLocale()
    {
        Queue::fake();

        Mail::to('test@mail.com')->queue(new SendingQueuedMailTestMail);

        Queue::assertPushed(SendQueuedMailable::class, function ($job) {
            return $job->middleware[0] instanceof RateLimited;
        });
    }

    public function testMailIsSentWhenRoutingQueue()
    {
        Queue::fake();

        Queue::route(Mailable::class, 'mail-queue', 'mail-connection');

        Mail::to('test@mail.com')->queue(new SendingQueuedMailTestMail);

        Queue::connection('mail-connection')->assertPushedOn('mail-queue', SendQueuedMailable::class);
    }

    public function testMailIsSentWhenForwardingQueue()
    {
        Queue::fake();

        Queue::forward('mail-queue', 'main', 'mail-connection');

        Mail::to('test@mail.com')->queue(new SendingQueuedForwardedMailTestMail);

        Queue::connection('mail-connection')->assertPushedOn('mail-queue', SendQueuedMailable::class);
    }

    public function testMailIsSentWithDelay()
    {
        Queue::fake();

        $delay = Carbon::now()->addMinutes(10);

        Mail::to('test@mail.com')->later($delay, new SendingQueuedMailTestMail);

        Queue::assertPushed(SendQueuedMailable::class, function ($job) use ($delay) {
            return $job->delay === $delay;
        });
    }
}

class SendingQueuedMailTestMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view');
    }

    public function middleware()
    {
        return [new RateLimited('limiter')];
    }
}

class SendingQueuedForwardedMailTestMail extends Mailable
{
    public $queue = 'mail-queue';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view');
    }
}
