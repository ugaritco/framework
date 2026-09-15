<?php

namespace Heritage\Http\Middleware;

use Heritage\Http\Response;
use Heritage\Support\Collection;
use Heritage\Support\Facades\Vite;

class AddLinkHeadersForPreloadedAssets
{
    /**
     * Configure the middleware.
     *
     * @param  int  $limit
     * @return string
     */
    public static function using($limit)
    {
        return static::class.':'.$limit;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|null  $limit
     * @return \Heritage\Http\Response
     */
    public function handle($request, $next, $limit = null)
    {
        return tap($next($request), function ($response) use ($limit) {
            if ($response instanceof Response && Vite::preloadedAssets() !== []) {
                $response->header('Link', (new Collection(Vite::preloadedAssets()))
                    ->when($limit, fn ($assets, $limit) => $assets->take($limit))
                    ->map(fn ($attributes, $url) => "<{$url}>; ".implode('; ', $attributes))
                    ->join(', '), false);
            }
        });
    }
}
