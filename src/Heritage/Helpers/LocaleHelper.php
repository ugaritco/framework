<?php

declare(strict_types=1);

namespace Heritage\Helpers;

use Carbon\Carbon;
use Heritage\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\Model;
use RuntimeException;

/**
 * Class LocaleHelper
 *
 * Provides utility methods for caching, querying, and resolving application
 * locales and their database identifiers and model instances.
 */
class LocaleHelper
{
    /**
     * Cached locale ID to avoid repeated queries.
     *
     * @var array<string, int|null>
     */
    protected static array $localeIds = [];

    /**
     * Cached locale Code to avoid repeated queries.
     *
     * @var array<int, string|null>
     */
    protected static array $localeCodes = [];

    /**
     * Get the locale ID based on the current application locale.
     * Queries the locales table only once and caches the result.
     *
     * @param  string|null  $code
     * @return int|null
     */
    public static function getLocaleId(?string $code = null): ?int
    {
        $currentLocale = $code ?? app()->getLocale();
        if (! isset(self::$localeIds[$currentLocale])) {
            [$languageCode, $countryCode] = array_pad(
                explode('-', $currentLocale),
                2,
                null
            );
            $query = self::localeQuery()
                ->whereHas('language', function ($q) use ($languageCode) {
                    $q->where('code', $languageCode);
                });
            if ($countryCode) {
                $query->whereHas('country', function ($q) use ($countryCode) {
                    $q->where('code', $countryCode);
                });
            }
            self::$localeIds[$currentLocale] = $query->value('id');
        }

        $localeId = self::$localeIds[$currentLocale];

        return $localeId !== null ? (int) $localeId : null;
    }

    /**
     * Resolve the active locale record for the current application locale.
     *
     * @param  bool  $withRelations
     * @return \Heritage\Database\Eloquent\Model|null
     */
    public static function getCurrentLocale(bool $withRelations = true): ?Model
    {
        $locale = self::getLocaleById(self::getLocaleId(), $withRelations);

        if ($locale !== null) {
            return $locale;
        }

        $query = self::localeQuery();

        if ($withRelations) {
            $query->with(['language', 'country']);
        }

        return (clone $query)->where('default', true)->first()
            ?? $query->first();
    }

    /**
     * Get the locale code (like "en" or "ar-SA") based on the locale ID.
     *
     * @param  int|null  $id
     * @return string|null
     */
    public static function getLocaleCode(?int $id = null): ?string
    {
        if ($id === null) {
            return app()->getLocale();
        }
        if (! isset(self::$localeCodes[$id])) {
            $locale = self::localeQuery()->with(['language', 'country'])->find($id);

            if (! $locale) {
                self::$localeCodes[$id] = null;
            } else {
                $code = $locale->language->code;
                if ($locale->country) {
                    $code .= '-'.$locale->country->code;
                }
                self::$localeCodes[$id] = $code;
            }
        }

        return self::$localeCodes[$id];
    }

    /**
     * Get the Locale model by its ID (cached).
     *
     * @param  int|null  $id
     * @param  bool  $withRelations
     * @return \Heritage\Database\Eloquent\Model|null
     */
    public static function getLocaleById(?int $id, bool $withRelations = true): ?Model
    {
        if ($id === null) {
            return null;
        }

        static $localeModels = [];

        if (! array_key_exists($id, $localeModels)) {
            $query = self::localeQuery();

            if ($withRelations) {
                $query->with(['language', 'country']);
            }

            $localeModels[$id] = $query->find($id);
        }

        return $localeModels[$id];
    }

    /**
     * Format a date based on the application's locale using Carbon only.
     *
     * @param  string  $date
     * @return string
     */
    public static function formatLocalizedDate(string $date): string
    {
        $carbonDate = Carbon::parse($date);

        $locale = app()->getLocale();
        $carbonDate->locale($locale);
        if ($locale === 'ar') {
            return $carbonDate->translatedFormat('F Y');
        }

        return $carbonDate->format('F, Y');
    }

    /**
     * Resolve the Eloquent query builder for locales.
     *
     * @return \Heritage\Database\Eloquent\Builder
     */
    protected static function localeQuery(): Builder
    {
        $model = config('artifacts.locale_model')
            ?? config('ugarit.locale_model')
            ?? config('levantc.locale_model')
            ?? (class_exists(\Ugarit\Artifacts\I18n\Models\Locale\Locale::class) ? \Ugarit\Artifacts\I18n\Models\Locale\Locale::class : null);

        if (! is_string($model) || $model === '' || ! is_subclass_of($model, Model::class)) {
            throw new RuntimeException(
                'LocaleHelper requires a valid Eloquent model class for locales.'
            );
        }

        return $model::query();
    }
}
