<?php

declare(strict_types=1);

namespace Heritage\Translation;

use Heritage\Contracts\Translation\LocaleResolver;
use Throwable;

/**
 * Default locale resolver: locales come from config/locale.php and translation tables
 * store the locale code itself in a "locale" column. Works without the i18n artifact.
 */
class ConfigLocaleResolver implements LocaleResolver
{
    public function current(): string
    {
        try {
            return (string) app()->getLocale();
        } catch (Throwable) {
            return $this->default();
        }
    }

    public function default(): string
    {
        return (string) $this->config('locale.default_locale', $this->config('app.locale', 'en'));
    }

    public function fallback(): string
    {
        return $this->config('locale.fallback_locale', $this->config('app.fallback_locale', 'en'));
    }

    public function available(): array
    {
        $codes = array_keys((array) $this->config('locale.available_locales', []));

        return $codes !== [] ? $codes : array_values(array_unique([$this->current(), $this->fallback()]));
    }

    public function isAvailable(string $code): bool
    {
        return in_array($code, $this->available(), true);
    }

    public function storageColumn(): string
    {
        return 'locale';
    }

    public function storageTable(): ?string
    {
        return null;
    }

    public function toStorage(string $code): string|int|null
    {
        return $this->isAvailable($code) ? $code : null;
    }

    public function fromStorage(string|int $value): ?string
    {
        return (string) $value;
    }

    public function label(string $code): string
    {
        return (string) ($this->config("locale.available_locales.{$code}.native") ?? strtoupper($code));
    }

    /**
     * Read a config value, tolerating a missing config repository (e.g. bare kernel tests).
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        try {
            return config($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}
