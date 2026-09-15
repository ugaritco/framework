<?php

namespace Heritage\Support\Facades;

use Heritage\Support\Testing\Fakes\MailFake;

/**
 * @method static \Heritage\Contracts\Mail\Mailer mailer(\UnitEnum|string|null $name = null)
 * @method static \Heritage\Mail\Mailer driver(\UnitEnum|string|null $driver = null)
 * @method static \Heritage\Mail\Mailer build(array $config)
 * @method static \Symfony\Component\Mailer\Transport\TransportInterface createSymfonyTransport(array $config)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static void purge(\UnitEnum|string|null $name = null)
 * @method static \Heritage\Mail\MailManager extend(string $driver, \Closure $callback)
 * @method static \Heritage\Contracts\Foundation\Application getApplication()
 * @method static \Heritage\Mail\MailManager setApplication(\Heritage\Contracts\Foundation\Application $app)
 * @method static \Heritage\Mail\MailManager forgetMailers()
 * @method static void alwaysFrom(string $address, string|null $name = null)
 * @method static void alwaysReplyTo(string $address, string|null $name = null)
 * @method static void alwaysReturnPath(string $address)
 * @method static void alwaysTo(string $address, string|null $name = null)
 * @method static \Heritage\Mail\PendingMail to(mixed $users, string|null $name = null)
 * @method static \Heritage\Mail\PendingMail cc(mixed $users, string|null $name = null)
 * @method static \Heritage\Mail\PendingMail bcc(mixed $users, string|null $name = null)
 * @method static \Heritage\Mail\SentMessage|null html(string $html, mixed $callback)
 * @method static \Heritage\Mail\SentMessage|null raw(string $text, mixed $callback)
 * @method static \Heritage\Mail\SentMessage|null plain(string $view, array $data, mixed $callback)
 * @method static string render(string|array $view, array $data = [])
 * @method static \Heritage\Mail\SentMessage|null send(\Heritage\Contracts\Mail\Mailable|string|array $view, array $data = [], \Closure|string|null $callback = null)
 * @method static \Heritage\Mail\SentMessage|null sendNow(\Heritage\Contracts\Mail\Mailable|string|array $mailable, array $data = [], \Closure|string|null $callback = null)
 * @method static mixed queue(\Heritage\Contracts\Mail\Mailable $view, \BackedEnum|string|null $queue = null)
 * @method static mixed onQueue(\BackedEnum|string|null $queue, \Heritage\Contracts\Mail\Mailable $view)
 * @method static mixed queueOn(string $queue, \Heritage\Contracts\Mail\Mailable $view)
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, \Heritage\Contracts\Mail\Mailable $view, string|null $queue = null)
 * @method static mixed laterOn(string $queue, \DateTimeInterface|\DateInterval|int $delay, \Heritage\Contracts\Mail\Mailable $view)
 * @method static \Symfony\Component\Mailer\Transport\TransportInterface getSymfonyTransport()
 * @method static \Heritage\Contracts\View\Factory getViewFactory()
 * @method static void setSymfonyTransport(\Symfony\Component\Mailer\Transport\TransportInterface $transport)
 * @method static \Heritage\Mail\Mailer setQueue(\Heritage\Contracts\Queue\Factory $queue)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static void assertSent(string|\Closure $mailable, callable|array|string|int|null $callback = null)
 * @method static void assertSentTimes(string $mailable, int $times = 1)
 * @method static void assertSentOnce(string $mailable)
 * @method static void assertNotOutgoing(string|\Closure $mailable, callable|null $callback = null)
 * @method static void assertNotSent(string|\Closure $mailable, callable|array|string|null $callback = null)
 * @method static void assertNothingOutgoing()
 * @method static void assertNothingSent()
 * @method static void assertQueued(string|\Closure $mailable, callable|array|string|int|null $callback = null)
 * @method static void assertQueuedTimes(string $mailable, int $times = 1)
 * @method static void assertQueuedOnce(string $mailable)
 * @method static void assertNotQueued(string|\Closure $mailable, callable|array|string|null $callback = null)
 * @method static void assertNothingQueued()
 * @method static void assertSentCount(int $count)
 * @method static void assertQueuedCount(int $count)
 * @method static void assertOutgoingCount(int $count)
 * @method static \Heritage\Support\Collection sent(string|\Closure $mailable, callable|null $callback = null)
 * @method static bool hasSent(string $mailable)
 * @method static \Heritage\Support\Collection queued(string|\Closure $mailable, callable|null $callback = null)
 * @method static bool hasQueued(string $mailable)
 *
 * @see \Heritage\Mail\MailManager
 * @see \Heritage\Support\Testing\Fakes\MailFake
 */
class Mail extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Heritage\Support\Testing\Fakes\MailFake
     */
    public static function fake()
    {
        $actualMailManager = static::isFake()
            ? static::getFacadeRoot()->manager
            : static::getFacadeRoot();

        return tap(new MailFake($actualMailManager), function ($fake) {
            static::swap($fake);
        });
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mail.manager';
    }
}
