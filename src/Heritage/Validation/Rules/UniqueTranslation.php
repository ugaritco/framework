<?php

declare(strict_types=1);

namespace Heritage\Validation\Rules;

use Closure;
use Heritage\Contracts\Validation\ValidationRule;
use Heritage\Database\Eloquent\Model;
use Heritage\Support\Str;
use Heritage\Translation\Locales;

/**
 * A translated value must be unique within its locale, like the unique(locale, column)
 * index Schema::createTranslatable() builds for a unique translatable column.
 *
 * The locale is the one given, or read from a "translations.{locale}.{field}" attribute;
 * the column defaults to the attribute's last segment. Soft-deleted translations are ignored
 * only where the index ignores them too (partial indexes).
 *
 *     'name' => ['required', new UniqueTranslation(Branch::class, ignore: $branch?->id)]
 */
class UniqueTranslation implements ValidationRule
{
    /**
     * @param  class-string<Model>  $model  The translatable model.
     * @param  string|null  $column  Translated column (default: the attribute's last segment).
     * @param  string|null  $locale  Locale code (default: read from the attribute).
     * @param  int|string|null  $ignore  Parent key whose own translations are ignored (updates).
     */
    public function __construct(
        protected string $model,
        protected ?string $column = null,
        protected ?string $locale = null,
        protected int|string|null $ignore = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $locale = $this->locale
            ?? (preg_match('/^translations\.([^.]+)\./', $attribute, $matches) ? $matches[1] : null);

        $storage = $locale === null ? null : Locales::resolver()->toStorage($locale);

        // Unknown locales are rejected by the locale rules.
        if ($storage === null || $value === null || $value === '') {
            return;
        }

        /** @var Model $parent */
        $parent = new $this->model;
        $translation = $parent->newTranslation();

        // Trashed rows still hold the unique index unless it is partial (not on MySQL / MariaDB).
        $countsTrashed = $parent->translationUsesSoftDeletes()
            && ! $translation->getConnection()->getSchemaBuilder()->supportsPartialIndexes();

        $exists = $translation->newQuery()
            ->when($countsTrashed, fn ($query) => $query->withTrashed())
            ->where($parent->getTranslationLocaleColumn(), $storage)
            ->where($this->column ?? Str::afterLast($attribute, '.'), $value)
            ->when($this->ignore !== null, fn ($query) => $query->where($parent->getTranslationForeignKey(), '!=', $this->ignore))
            ->exists();

        if ($exists) {
            $fail('validation.unique')->translate();
        }
    }
}
