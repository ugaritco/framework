<?php

namespace Heritage\Support\Facades;

use Heritage\Notifications\AnonymousNotifiable;
use Heritage\Notifications\ChannelManager;
use Heritage\Support\Testing\Fakes\NotificationFake;

/**
 * @method static void send(\Heritage\Support\Collection|mixed $notifiables, mixed $notification)
 * @method static void sendNow(\Heritage\Support\Collection|mixed $notifiables, mixed $notification, array|null $channels = null)
 * @method static mixed channel(\UnitEnum|string|null $name = null)
 * @method static mixed driver(\UnitEnum|string|null $driver = null)
 * @method static string getDefaultDriver()
 * @method static string deliversVia()
 * @method static void deliverVia(string $channel)
 * @method static \Heritage\Notifications\ChannelManager locale(string $locale)
 * @method static \Heritage\Notifications\ChannelManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Heritage\Contracts\Container\Container getContainer()
 * @method static \Heritage\Notifications\ChannelManager setContainer(\Heritage\Contracts\Container\Container $container)
 * @method static \Heritage\Notifications\ChannelManager forgetDrivers()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
 * @method static void assertSentOnDemand(string|\Closure $notification, callable|null $callback = null)
 * @method static void assertSentTo(mixed $notifiable, string|\Closure $notification, callable|null $callback = null)
 * @method static void assertSentOnDemandTimes(string $notification, int $times = 1)
 * @method static void assertSentOnDemandOnce(string $notification)
 * @method static void assertSentToTimes(mixed $notifiable, string $notification, int $times = 1)
 * @method static void assertSentToOnce(mixed $notifiable, string $notification)
 * @method static void assertNotSentTo(mixed $notifiable, string|\Closure $notification, callable|null $callback = null)
 * @method static void assertNothingSent()
 * @method static void assertNothingSentTo(mixed $notifiable)
 * @method static void assertSentTimes(string $notification, int $expectedCount)
 * @method static void assertCount(int $expectedCount)
 * @method static \Heritage\Support\Collection sent(mixed $notifiable, string $notification, callable|null $callback = null)
 * @method static bool hasSent(mixed $notifiable, string $notification)
 * @method static \Heritage\Support\Testing\Fakes\NotificationFake serializeAndRestore(bool $serializeAndRestore = true)
 * @method static array sentNotifications()
 *
 * @see \Heritage\Notifications\ChannelManager
 * @see \Heritage\Support\Testing\Fakes\NotificationFake
 */
class Notification extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Heritage\Support\Testing\Fakes\NotificationFake
     */
    public static function fake()
    {
        return tap(new NotificationFake, function ($fake) {
            static::swap($fake);
        });
    }

    /**
     * Begin sending a notification to an anonymous notifiable on the given channels.
     *
     * @param  array  $channels
     * @return \Heritage\Notifications\AnonymousNotifiable
     */
    public static function routes(array $channels)
    {
        $notifiable = new AnonymousNotifiable;

        foreach ($channels as $channel => $route) {
            $notifiable->route($channel, $route);
        }

        return $notifiable;
    }

    /**
     * Begin sending a notification to an anonymous notifiable.
     *
     * @param  string  $channel
     * @param  mixed  $route
     * @return \Heritage\Notifications\AnonymousNotifiable
     */
    public static function route($channel, $route)
    {
        return (new AnonymousNotifiable)->route($channel, $route);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ChannelManager::class;
    }
}
