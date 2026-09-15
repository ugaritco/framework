<?php

namespace Heritage\Notifications;

use Heritage\Contracts\Bus\Dispatcher as Bus;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Contracts\Notifications\Dispatcher as DispatcherContract;
use Heritage\Contracts\Notifications\Factory as FactoryContract;
use Heritage\Support\Manager;
use Heritage\Support\Queue\Concerns\ResolvesQueueRoutes;
use Heritage\Support\Traits\Macroable;
use InvalidArgumentException;

class ChannelManager extends Manager implements DispatcherContract, FactoryContract
{
    use Macroable, ResolvesQueueRoutes;

    /**
     * The resolved notification sender instance.
     *
     * @var \Heritage\Notifications\NotificationSender|null
     */
    protected $notificationSender;

    /**
     * The default channel used to deliver messages.
     *
     * @var string
     */
    protected $defaultChannel = 'mail';

    /**
     * The locale used when sending notifications.
     *
     * @var string|null
     */
    protected $locale;

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  \Heritage\Support\Collection|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        $this->resolveNotificationSender()->send($notifiables, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param  \Heritage\Support\Collection|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, ?array $channels = null)
    {
        $this->resolveNotificationSender()->sendNow($notifiables, $notification, $channels);
    }

    /**
     * Get a channel instance.
     *
     * @param  \UnitEnum|string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Get a driver instance.
     *
     * @param  \UnitEnum|string|null  $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        return parent::driver($driver);
    }

    /**
     * Create an instance of the database driver.
     *
     * @return \Heritage\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return $this->container->make(Channels\DatabaseChannel::class);
    }

    /**
     * Create an instance of the broadcast driver.
     *
     * @return \Heritage\Notifications\Channels\BroadcastChannel
     */
    protected function createBroadcastDriver()
    {
        return $this->container->make(Channels\BroadcastChannel::class);
    }

    /**
     * Create an instance of the mail driver.
     *
     * @return \Heritage\Notifications\Channels\MailChannel
     */
    protected function createMailDriver()
    {
        return $this->container->make(Channels\MailChannel::class);
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $e) {
            if (class_exists($driver)) {
                return $this->container->make($driver);
            }

            throw $e;
        }
    }

    /**
     * Resolve the NotificationSender instance.
     *
     * @return \Heritage\Notifications\NotificationSender
     */
    protected function resolveNotificationSender()
    {
        return $this->notificationSender ??= new NotificationSender(
            $this, $this->container->make(Bus::class), $this->container->make(Dispatcher::class), $this->locale
        );
    }

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultChannel;
    }

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function deliversVia()
    {
        return $this->getDefaultDriver();
    }

    /**
     * Set the default channel driver name.
     *
     * @param  string  $channel
     * @return void
     */
    public function deliverVia($channel)
    {
        $this->defaultChannel = $channel;
    }

    /**
     * Set the locale of notifications.
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
