<?php

namespace Heritage\Support\Facades;

use Heritage\Http\Client\Factory;

/**
 * @method static \Heritage\Http\Client\Factory globalMiddleware(callable $middleware)
 * @method static \Heritage\Http\Client\Factory globalRequestMiddleware(callable $middleware)
 * @method static \Heritage\Http\Client\Factory globalResponseMiddleware(callable $middleware)
 * @method static \Heritage\Http\Client\Factory globalOptions(\Closure|array $options)
 * @method static mixed withoutGlobalConfiguration(\Closure $callback)
 * @method static \GuzzleHttp\Promise\PromiseInterface response(\Psr\Http\Message\StreamInterface|array|string|resource|null $body = null, int $status = 200, array $headers = [])
 * @method static \GuzzleHttp\Psr7\Response psr7Response(\Psr\Http\Message\StreamInterface|array|string|resource|null $body = null, int $status = 200, array $headers = [])
 * @method static \Heritage\Http\Client\RequestException failedRequest(\Psr\Http\Message\StreamInterface|array|string|resource|null $body = null, int $status = 200, array $headers = [])
 * @method static \Closure failedConnection(string|null $message = null)
 * @method static \Heritage\Http\Client\ResponseSequence sequence(array $responses = [])
 * @method static bool preventingStrayRequests()
 * @method static \Heritage\Http\Client\Factory allowStrayRequests(array|null $only = null)
 * @method static \Heritage\Http\Client\Factory record()
 * @method static void recordRequestResponsePair(\Heritage\Http\Client\Request $request, \Heritage\Http\Client\Response|null $response)
 * @method static void assertSent(callable|\Closure $callback)
 * @method static void assertSentInOrder(array $callbacks)
 * @method static void assertNotSent(callable|\Closure $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertSequencesAreEmpty()
 * @method static \Heritage\Support\Collection recorded(\Closure|callable $callback = null)
 * @method static \Heritage\Http\Client\PendingRequest createPendingRequest()
 * @method static \Heritage\Contracts\Events\Dispatcher|null getDispatcher()
 * @method static array getGlobalMiddleware()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static \Heritage\Http\Client\PendingRequest baseUrl(string $url)
 * @method static \Heritage\Http\Client\PendingRequest withBody(\Psr\Http\Message\StreamInterface|string $content, string $contentType = 'application/json')
 * @method static \Heritage\Http\Client\PendingRequest asJson()
 * @method static \Heritage\Http\Client\PendingRequest asForm()
 * @method static \Heritage\Http\Client\PendingRequest attach(string|array $name, string|resource $contents = '', string|null $filename = null, array $headers = [])
 * @method static \Heritage\Http\Client\PendingRequest asMultipart()
 * @method static \Heritage\Http\Client\PendingRequest bodyFormat(string $format)
 * @method static \Heritage\Http\Client\PendingRequest withQueryParameters(array $parameters)
 * @method static \Heritage\Http\Client\PendingRequest contentType(string $contentType)
 * @method static \Heritage\Http\Client\PendingRequest acceptJson()
 * @method static \Heritage\Http\Client\PendingRequest accept(string $contentType)
 * @method static \Heritage\Http\Client\PendingRequest withHeaders(array $headers)
 * @method static \Heritage\Http\Client\PendingRequest withHeader(string $name, mixed $value)
 * @method static \Heritage\Http\Client\PendingRequest replaceHeaders(array $headers)
 * @method static \Heritage\Http\Client\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \Heritage\Http\Client\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \Heritage\Http\Client\PendingRequest withNtlmAuth(string $username, string $password)
 * @method static \Heritage\Http\Client\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \Heritage\Http\Client\PendingRequest withUserAgent(string|bool $userAgent)
 * @method static \Heritage\Http\Client\PendingRequest withUrlParameters(array $parameters = [])
 * @method static \Heritage\Http\Client\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \Heritage\Http\Client\PendingRequest maxRedirects(int $max)
 * @method static \Heritage\Http\Client\PendingRequest withoutRedirecting()
 * @method static \Heritage\Http\Client\PendingRequest withoutVerifying()
 * @method static \Heritage\Http\Client\PendingRequest sink(string|resource $to)
 * @method static \Heritage\Http\Client\PendingRequest timeout(int|float $seconds)
 * @method static \Heritage\Http\Client\PendingRequest connectTimeout(int|float $seconds)
 * @method static \Heritage\Http\Client\PendingRequest retry(array|int $times, \Closure|int $sleepMilliseconds = 0, callable|null $when = null, bool $throw = true)
 * @method static \Heritage\Http\Client\PendingRequest withOptions(array $options)
 * @method static \Heritage\Http\Client\PendingRequest withMiddleware(callable $middleware)
 * @method static \Heritage\Http\Client\PendingRequest withRequestMiddleware(callable $middleware)
 * @method static \Heritage\Http\Client\PendingRequest withResponseMiddleware(callable $middleware)
 * @method static \Heritage\Http\Client\PendingRequest withAttributes(array $attributes)
 * @method static \Heritage\Http\Client\PendingRequest beforeSending(callable $callback)
 * @method static \Heritage\Http\Client\PendingRequest afterResponse(callable $callback)
 * @method static \Heritage\Http\Client\PendingRequest throw(callable|null $callback = null)
 * @method static \Heritage\Http\Client\PendingRequest throwIf(callable|bool $condition)
 * @method static \Heritage\Http\Client\PendingRequest throwUnless(callable|bool $condition)
 * @method static \Heritage\Http\Client\PendingRequest dump()
 * @method static \Heritage\Http\Client\PendingRequest dd()
 * @method static \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface get(string $url, array|string|null $query = null)
 * @method static \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface head(string $url, array|string|null $query = null)
 * @method static \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface query(string $url, array|\JsonSerializable|\Heritage\Contracts\Support\Arrayable $data = [])
 * @method static \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface post(string $url, array|\JsonSerializable|\Heritage\Contracts\Support\Arrayable $data = [])
 * @method static \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface patch(string $url, array|\JsonSerializable|\Heritage\Contracts\Support\Arrayable $data = [])
 * @method static \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface put(string $url, array|\JsonSerializable|\Heritage\Contracts\Support\Arrayable $data = [])
 * @method static \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface delete(string $url, array|\JsonSerializable|\Heritage\Contracts\Support\Arrayable $data = [])
 * @method static array pool(callable $callback, int|null $concurrency = 0)
 * @method static \Heritage\Http\Client\Batch batch(callable $callback)
 * @method static \Heritage\Http\Client\Response|\Heritage\Http\Client\Promises\LazyPromise send(string $method, string $url, array $options = [])
 * @method static \GuzzleHttp\Client buildClient()
 * @method static \GuzzleHttp\Client createClient(\GuzzleHttp\HandlerStack $handlerStack)
 * @method static \GuzzleHttp\HandlerStack buildHandlerStack()
 * @method static \GuzzleHttp\HandlerStack pushHandlers(\GuzzleHttp\HandlerStack $handlerStack)
 * @method static \Closure buildBeforeSendingHandler()
 * @method static \Closure buildRecorderHandler()
 * @method static \Closure buildStubHandler()
 * @method static \Psr\Http\Message\RequestInterface runBeforeSendingCallbacks(\Psr\Http\Message\RequestInterface $request, array $options)
 * @method static array mergeOptions(array ...$options)
 * @method static \Heritage\Http\Client\PendingRequest stub(callable $callback)
 * @method static bool isAllowedRequestUrl(string $url)
 * @method static \Heritage\Http\Client\PendingRequest async(bool $async = true)
 * @method static \GuzzleHttp\Promise\PromiseInterface|null getPromise()
 * @method static \Heritage\Http\Client\PendingRequest truncateExceptionsAt(int $length)
 * @method static \Heritage\Http\Client\PendingRequest dontTruncateExceptions()
 * @method static \Heritage\Http\Client\PendingRequest setClient(\GuzzleHttp\Client $client)
 * @method static \Heritage\Http\Client\PendingRequest setHandler(callable $handler)
 * @method static array getOptions()
 * @method static \Heritage\Http\Client\PendingRequest|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Heritage\Http\Client\PendingRequest|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \Heritage\Http\Client\Factory
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  \Closure|array|null  $callback
     * @return \Heritage\Http\Client\Factory
     */
    public static function fake($callback = null)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }

    /**
     * Register a response sequence for the given URL pattern.
     *
     * @param  string  $urlPattern
     * @return \Heritage\Http\Client\ResponseSequence
     */
    public static function fakeSequence(string $urlPattern = '*')
    {
        $fake = tap(static::getFacadeRoot(), function ($fake) {
            static::swap($fake);
        });

        return $fake->fakeSequence($urlPattern);
    }

    /**
     * Indicate that an exception should be thrown if any request is not faked.
     *
     * @param  bool  $prevent
     * @return \Heritage\Http\Client\Factory
     */
    public static function preventStrayRequests($prevent = true)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($prevent) {
            static::swap($fake->preventStrayRequests($prevent));
        });
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param  string  $url
     * @param  \Heritage\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface|callable  $callback
     * @return \Heritage\Http\Client\Factory
     */
    public static function stubUrl($url, $callback)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($url, $callback) {
            static::swap($fake->stubUrl($url, $callback));
        });
    }
}
