<?php

declare(strict_types=1);

namespace Heritage\Routing;

use Heritage\Contracts\Translation\LocaleResolver;
use Throwable;

/**
 * Locale codes that public localized routes accept.
 *
 * Configured language codes (ar, en) stay first. Active locales from the bound
 * resolver are added so a regional code such as ar-PS is a real URL prefix.
 */
class LocalizedCodes
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $codes = function_exists('config')
            ? array_keys(config('locale.available_locales', ['ar' => [], 'en' => []]))
            : ['ar', 'en'];

        try {
            if (function_exists('app') && app()->bound(LocaleResolver::class)) {
                $available = app(LocaleResolver::class)->available();

                if (is_array($available)) {
                    $codes = array_merge($codes, array_values($available));
                }
            }
        } catch (Throwable) {
            // Route registration still falls back to the configured languages.
        }

        $default = function_exists('config') ? config('locale.default_locale') : null;

        if (is_string($default) && $default !== '') {
            $codes[] = $default;
        }

        $codes = array_values(array_unique(array_filter(
            $codes,
            static fn ($code) => is_string($code) && preg_match('/^[A-Za-z]{2}([_-][A-Za-z0-9]{2,8})?$/', $code) === 1,
        )));

        return $codes !== [] ? $codes : ['ar', 'en'];
    }

    /**
     * Language code used to load translation files.
     *
     * ar-PS has no lang/ar-PS directory; the packaged strings live under lang/ar.
     */
    public static function translation(string $locale): string
    {
        $configured = function_exists('config')
            ? array_keys(config('locale.available_locales', []))
            : [];

        if (in_array($locale, $configured, true)) {
            return $locale;
        }

        $language = preg_split('/[_-]/', $locale, 2)[0] ?: $locale;

        if ($configured === [] || in_array($language, $configured, true)) {
            return $language;
        }

        return $locale;
    }
}
