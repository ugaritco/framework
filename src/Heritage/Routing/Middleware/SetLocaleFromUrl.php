<?php

namespace Heritage\Routing\Middleware;

use Closure;
use Heritage\Http\Request;
use Heritage\Routing\LocalizedCodes;
use Heritage\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  \Closure(\Heritage\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $locale
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $locale = $locale ?? $request->segment(1);

        $availableLocales = LocalizedCodes::all();

        if (! in_array($locale, $availableLocales, true)) {
            $locale = function_exists('config')
                ? (config('locale.default_locale') ?? config('app.locale', 'ar'))
                : 'ar';
        }

        app()->setLocale(LocalizedCodes::translation($locale));

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        if (class_exists(View::class)) {
            View::share('locale', LocalizedCodes::translation($locale));
            View::share('seo_alternates', $this->generateSeoAlternates($request, $availableLocales));
        }

        return $next($request);
    }

    /**
     * Generate alternate hreflang URLs for SEO indexing.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  array<string>  $availableLocales
     * @return array<string, string>
     */
    protected function generateSeoAlternates(Request $request, array $availableLocales): array
    {
        $alternates = [];
        $currentUri = $request->path();
        $segments = explode('/', trim($currentUri, '/'));

        foreach ($availableLocales as $loc) {
            $clonedSegments = $segments;
            if (! empty($clonedSegments) && in_array($clonedSegments[0], $availableLocales, true)) {
                $clonedSegments[0] = $loc;
            } else {
                array_unshift($clonedSegments, $loc);
            }

            $alternates[$loc] = url(implode('/', $clonedSegments));
        }

        return $alternates;
    }
}
