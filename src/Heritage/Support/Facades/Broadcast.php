<?php

namespace Heritage\Support\Facades;

use Heritage\Contracts\Broadcasting\Factory as BroadcastingFactoryContract;

/**
 * @method static void routes(array|null $attributes = null)
 * @method static void userRoutes(array|null $attributes = null)
 * @method static void channelRoutes(array|null $attributes = null)
 * @method static string|null socket(\Heritage\Http\Request|null $request = null)
 * @method static \Heritage\Broadcasting\AnonymousEvent on(\Heritage\Broadcasting\Channel|array|string $channels)
 * @method static \Heritage\Broadcasting\AnonymousEvent private(string $channel)
 * @method static \Heritage\Broadcasting\AnonymousEvent presence(string $channel)
 * @method static \Heritage\Broadcasting\PendingBroadcast event(mixed $event = null)
 * @method static void queue(mixed $event)
 * @method static mixed connection(\UnitEnum|string|null $name = null)
 * @method static mixed driver(\UnitEnum|string|null $name = null)
 * @method static \Pusher\Pusher pusher(array $config)
 * @method static \Ably\AblyRest ably(array $config)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static void purge(\UnitEnum|string|null $name = null)
 * @method static \Heritage\Broadcasting\BroadcastManager extend(string $driver, \Closure $callback)
 * @method static \Heritage\Contracts\Foundation\Application getApplication()
 * @method static \Heritage\Broadcasting\BroadcastManager setApplication(\Heritage\Contracts\Foundation\Application $app)
 * @method static \Heritage\Broadcasting\BroadcastManager forgetDrivers()
 * @method static \Symfony\Component\Mercure\HubInterface mercure(array $config)
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
 * @method static mixed auth(\Heritage\Http\Request $request)
 * @method static mixed validAuthenticationResponse(\Heritage\Http\Request $request, mixed $result)
 * @method static void broadcast(array $channels, string $event, array $payload = [])
 * @method static array|null resolveAuthenticatedUser(\Heritage\Http\Request $request)
 * @method static void resolveAuthenticatedUserUsing(\Closure $callback)
 * @method static \Heritage\Broadcasting\Broadcasters\Broadcaster channel(\Heritage\Contracts\Broadcasting\HasBroadcastChannel|string $channel, callable|string $callback, array $options = [])
 * @method static \Heritage\Support\Collection getChannels()
 *
 * @see \Heritage\Broadcasting\BroadcastManager
 * @see \Heritage\Broadcasting\Broadcasters\Broadcaster
 */
class Broadcast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BroadcastingFactoryContract::class;
    }
}
