<?php

declare(strict_types=1);

namespace Heritage\Contracts\Translation;

/**
 * Single source of truth for locales used by translatable models.
 *
 * Every layer speaks in locale codes ("ar", "en"); only the resolver knows how a code
 * is stored in translation tables (the code itself, or a locale_id from the i18n artifact).
 */
interface LocaleResolver
{
    /**
     * The active locale code.
     */
    public function current(): string;

    /**
     * The application's default locale code.
     */
    public function default(): string;

    /**
     * The fallback locale code.
     */
    public function fallback(): string;

    /**
     * Codes of all locales the application accepts.
     *
     * @return array<int, string>
     */
    public function available(): array;

    /**
     * Whether a locale code is accepted.
     */
    public function isAvailable(string $code): bool;

    /**
     * Name of the column that stores the locale in translation tables ("locale" or "locale_id").
     */
    public function storageColumn(): string;

    /**
     * The table the locale column references (e.g. "locales"), or null when codes are stored.
     */
    public function storageTable(): ?string;

    /**
     * The value stored in translation tables for a code, or null when the code is unknown.
     */
    public function toStorage(string $code): string|int|null;

    /**
     * The code for a stored value, or null when the value is unknown.
     */
    public function fromStorage(string|int $value): ?string;

    /**
     * A human-readable name for a code (e.g. "العربية"), used in validation messages.
     */
    public function label(string $code): string;
}
