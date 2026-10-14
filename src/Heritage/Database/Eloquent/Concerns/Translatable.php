<?php

declare(strict_types=1);

namespace Heritage\Database\Eloquent\Concerns;

use Heritage\Contracts\Translation\LocaleResolver;
use Heritage\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Relations\HasMany;
use Heritage\Database\Eloquent\Relations\HasOne;
use Heritage\Database\Eloquent\RuntimeTranslation;
use Heritage\Database\Eloquent\SoftDeletes;
use Heritage\Support\Str;
use Heritage\Translation\Locales;
use InvalidArgumentException;

/**
 * Native multilingual support for Eloquent models.
 *
 * Every public method speaks in locale codes ("ar", "en"). How a code is stored in the
 * translation table (the code itself, or a locale_id) is delegated to the LocaleResolver,
 * so the kernel works without the i18n artifact and uses it when it is installed.
 *
 * Canonical input shape: ['translations' => ['ar' => ['name' => '...'], 'en' => [...]]].
 */
trait Translatable
{
    /**
     * Translation models queued for saving, keyed by locale code.
     *
     * @var array<string, \Heritage\Database\Eloquent\Model>
     */
    protected array $pendingTranslations = [];

    /**
     * Locale codes whose translation is deleted on save (sent as null in the payload).
     *
     * @var array<string, true>
     */
    protected array $pendingTranslationDeletions = [];

    /**
     * Translations already resolved for this instance, keyed by locale code (null = none).
     *
     * @var array<string, \Heritage\Database\Eloquent\Model|null>
     */
    protected array $resolvedTranslations = [];

    /**
     * Boot the translatable trait for the model.
     */
    public static function bootTranslatable(): void
    {
        // Eager-load the current and fallback translations so reading translated
        // attributes on a collection does not run one query per model. Single-record
        // queries (find(), first()) skip it: a lazy load costs the same query, only when used.
        static::addGlobalScope('localizedTranslations', function (Builder $builder) {
            $model = $builder->getModel();

            if ($model->isTranslatable() && $model->shouldEagerLoadTranslations() && $builder->getQuery()->limit !== 1) {
                $builder->with('localizedTranslations');
            }
        });

        // Pending translations are saved by Model::finishSave(), which also runs for quiet saves.

        static::deleting(function (Model $model) {
            // Soft deletes keep translations so a restored record keeps its names.
            if (! $model->isTranslatable() || (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting())) {
                return;
            }

            $model->translationUsesSoftDeletes()
                ? $model->translations()->withTrashed()->forceDelete()
                : $model->translations()->delete();
        });
    }

    /**
     * Whether the translation model soft deletes its rows (uses SoftDeletes).
     */
    public function translationUsesSoftDeletes(): bool
    {
        static $uses = [];

        $class = $this->getTranslationModel();

        return $uses[$class] ??= in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    /**
     * The locale resolver shared by all translatable models.
     */
    public static function localeResolver(): LocaleResolver
    {
        return Locales::resolver();
    }

    /**
     * Determine if the model is configured for translations.
     */
    public function isTranslatable(): bool
    {
        return ! empty($this->getTranslatable());
    }

    /**
     * Get the list of translatable attribute keys.
     *
     * @return array<int, string>
     */
    public function getTranslatable(): array
    {
        return property_exists($this, 'translatable') ? (array) $this->translatable : [];
    }

    /**
     * Determine if a given attribute key is translatable.
     */
    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->getTranslatable(), true);
    }

    /**
     * Get the fallback column map for translatable attributes (e.g. ['name' => 'slug']).
     *
     * @return array<string, string>
     */
    public function getTranslatableFallback(): array
    {
        return property_exists($this, 'translatableFallback') ? (array) $this->translatableFallback : [];
    }

    /**
     * Should a missing translation be queried on demand (lazy load).
     */
    public function isLoadTranslationOnDemand(): bool
    {
        return property_exists($this, 'loadTranslationOnDemand') ? (bool) $this->loadTranslationOnDemand : true;
    }

    /**
     * Should queries eager-load the current and fallback translations.
     */
    public function shouldEagerLoadTranslations(): bool
    {
        return property_exists($this, 'eagerLoadTranslations') ? (bool) $this->eagerLoadTranslations : true;
    }

    /**
     * Get the related translation model class name.
     */
    public function getTranslationModel(): string
    {
        if (property_exists($this, 'translationModel') && ! empty($this->translationModel)) {
            return $this->translationModel;
        }

        $conventional = static::class.'Translation';
        $ref = new \ReflectionClass(static::class);
        $expectedFile = dirname($ref->getFileName()).'/'.class_basename($conventional).'.php';

        if (file_exists($expectedFile) && class_exists($conventional)) {
            return $conventional;
        }

        // No translation class: a RuntimeTranslation configured for this model is used.
        return RuntimeTranslation::class;
    }

    /**
     * Whether a child route parameter names this model's translation, so scoped bindings
     * resolve it through translations(): "translation", "{model}_translation" or the
     * translation class name (snake or camel case).
     *
     * @param  string  $childType  The child route parameter name.
     * @return bool
     */
    protected function isTranslationRouteChild(string $childType): bool
    {
        if (! $this->isTranslatable()) {
            return false;
        }

        return in_array(Str::camel($childType), [
            'translation',
            Str::camel(class_basename(static::class)).'Translation',
            Str::camel(class_basename($this->getTranslationModel())),
        ], true);
    }

    /**
     * Create a new (unsaved) translation model instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newTranslation(array $attributes = []): Model
    {
        $class = $this->getTranslationModel();

        $instance = $class === RuntimeTranslation::class
            ? RuntimeTranslation::for($this, $attributes)
            : new $class($attributes);

        if (! $instance->getConnectionName()) {
            $instance->setConnection($this->getConnectionName());
        }

        return $instance;
    }

    /**
     * Get the table name for translations.
     */
    public function getTranslationTable(): string
    {
        if (property_exists($this, 'translationTable') && ! empty($this->translationTable)) {
            return $this->translationTable;
        }

        return Str::singular($this->getTable()).'_translations';
    }

    /**
     * Get the foreign key for the translation model relationship.
     */
    public function getTranslationForeignKey(): string
    {
        if (property_exists($this, 'translationForeignKey') && ! empty($this->translationForeignKey)) {
            return $this->translationForeignKey;
        }

        if (property_exists($this, 'table') && ! empty($this->table)) {
            return Str::singular($this->table).'_id';
        }

        return $this->getForeignKey();
    }

    /**
     * The column storing the locale in the translation table.
     */
    public function getTranslationLocaleColumn(): string
    {
        static $columns = [];

        $resolver = static::localeResolver();
        $key = static::class.'|'.$resolver::class;

        if (! isset($columns[$key])) {
            $translation = $this->newTranslation();

            $columns[$key] = method_exists($translation, 'getLocaleColumn')
                ? $translation->getLocaleColumn()
                : $resolver->storageColumn();
        }

        return $columns[$key];
    }

    /**
     * All translations of this model.
     */
    public function translations(): HasMany
    {
        $instance = $this->newTranslation();

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->qualifyColumn($this->getTranslationForeignKey()), $this->getKeyName()
        );
    }

    /**
     * The translations for the current and fallback locales (eager-loaded by default).
     */
    public function localizedTranslations(): HasMany
    {
        $values = array_values(array_filter(
            array_map(fn ($code) => $this->toLocaleStorage($code), $this->localizedCodes()),
            fn ($value) => $value !== null
        ));

        return $this->translations()->whereIn($this->getTranslationLocaleColumn(), $values);
    }

    /**
     * The translation for the given (or current) locale code.
     */
    public function translation(?string $locale = null): HasOne
    {
        $locale ??= static::localeResolver()->current();
        $instance = $this->newTranslation();

        return $this->newHasOne(
            $instance->newQuery(), $this, $instance->qualifyColumn($this->getTranslationForeignKey()), $this->getKeyName()
        )->where($this->getTranslationLocaleColumn(), $this->toLocaleStorage($locale));
    }

    /**
     * Get the translation model for the given (or current) locale code.
     */
    public function translate(?string $locale = null): ?Model
    {
        $locale ??= static::localeResolver()->current();

        if (isset($this->pendingTranslations[$locale])) {
            return $this->pendingTranslations[$locale];
        }

        if (isset($this->pendingTranslationDeletions[$locale])) {
            return null;
        }

        if (array_key_exists($locale, $this->resolvedTranslations)) {
            return $this->resolvedTranslations[$locale];
        }

        $storage = $this->toLocaleStorage($locale);

        if ($storage === null) {
            return null;
        }

        $column = $this->getTranslationLocaleColumn();
        $matches = fn (Model $translation) => (string) $translation->getAttribute($column) === (string) $storage;

        // Loaded relations: "translations" holds every locale, "localizedTranslations"
        // holds the current and fallback locales, "translation" a single one.
        foreach (['translations', 'localizedTranslations'] as $relation) {
            if (! $this->relationLoaded($relation)) {
                continue;
            }

            $found = $this->getRelation($relation)->first($matches);

            if ($found || $relation === 'translations' || in_array($locale, $this->localizedCodes(), true)) {
                return $this->resolvedTranslations[$locale] = $found;
            }
        }

        if ($this->relationLoaded('translation') && ($single = $this->getRelation('translation')) && $matches($single)) {
            return $this->resolvedTranslations[$locale] = $single;
        }

        if (! $this->exists || ! $this->isLoadTranslationOnDemand()) {
            return null;
        }

        return $this->resolvedTranslations[$locale] = $this->translation($locale)->first();
    }

    /**
     * Get the translation for the given locale or, when missing, the fallback locale.
     */
    public function translateOrDefault(?string $locale = null): ?Model
    {
        return $this->translate($locale) ?? $this->translate(static::localeResolver()->fallback());
    }

    /**
     * All stored translations in the canonical shape, keyed by locale code — the same shape
     * the model accepts in fill() — for API resources and edit forms:
     * ['ar' => ['id' => 5, 'name' => '...', 'locale' => 'ar', 'created_at' => ...], ...].
     *
     * Loads the "translations" relation when it is not loaded yet.
     *
     * @return array<string, array<string, mixed>>
     */
    public function translationsToArray(): array
    {
        $resolver = static::localeResolver();
        $column = $this->getTranslationLocaleColumn();
        $result = [];

        foreach ($this->translations as $translation) {
            $stored = $translation->getAttributes()[$column] ?? null;
            $code = $stored === null ? null : $resolver->fromStorage($stored);

            if ($code !== null) {
                $result[$code] = $translation->attributesToArray() + ['locale' => $code];
            }
        }

        return $result;
    }

    /**
     * Check if a translation exists for the given (or current) locale.
     */
    public function hasTranslation(?string $locale = null): bool
    {
        return $this->translate($locale) !== null;
    }

    /**
     * Get a translated attribute value for the given (or current) locale, or null when empty.
     */
    public function getTranslatedAttribute(string $key, ?string $locale = null): mixed
    {
        $value = $this->translate($locale)?->getAttribute($key);

        return $value === null || $value === '' ? null : $value;
    }

    /**
     * Value used when the current locale has no translation:
     * the fallback locale's translation, then the $translatableFallback column.
     */
    public function getTranslatableFallbackValue(string $key): mixed
    {
        $resolver = static::localeResolver();

        if ($resolver->fallback() !== $resolver->current()
            && ($value = $this->getTranslatedAttribute($key, $resolver->fallback())) !== null) {
            return $value;
        }

        $column = $this->getTranslatableFallback()[$key] ?? null;

        if ($column !== null && $this->hasAttribute($column)) {
            return $this->getAttributeFromArray($column);
        }

        return null;
    }

    /**
     * Set a translation value; an array value sets several locales (['ar' => ..., 'en' => ...]).
     *
     * @throws InvalidArgumentException When the locale is not available.
     */
    public function setTranslation(string $key, mixed $value, ?string $locale = null): static
    {
        if (is_array($value) && $locale === null) {
            foreach ($value as $code => $localized) {
                $this->setTranslation($key, $localized, (string) $code);
            }

            return $this;
        }

        $locale ??= static::localeResolver()->current();

        $this->pendingTranslation($locale)->setAttribute($key, $value);

        return $this;
    }

    /**
     * Fill several attributes of one locale.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function fillTranslation(string $locale, array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setTranslation($key, $value, $locale);
        }

        return $this;
    }

    /**
     * Fill translations from the canonical ['code' => ['attr' => value]] payload.
     *
     * The payload is merged: locales it omits are left untouched. A locale given as null
     * (['en' => null]) has its translation deleted on save. Within a locale holding at least
     * one value, empty values ('' or null) clear their attribute; a locale whose values are
     * all empty is skipped. Non-translatable keys and unknown locales are skipped.
     *
     * @param  array<string, array<string, mixed>|null>  $translations
     */
    public function fillTranslations(array $translations): static
    {
        $translatable = array_flip($this->getTranslatable());

        foreach ($translations as $locale => $attributes) {
            $locale = (string) $locale;

            if ($this->toLocaleStorage($locale) === null) {
                continue;
            }

            if ($attributes === null) {
                $this->forgetTranslation($locale);

                continue;
            }

            if (! is_array($attributes)) {
                continue;
            }

            $attributes = array_map(
                fn ($value) => $value === '' ? null : $value,
                array_intersect_key($attributes, $translatable)
            );

            if (array_filter($attributes, fn ($value) => $value !== null) !== []) {
                $this->fillTranslation($locale, $attributes);
            }
        }

        return $this;
    }

    /**
     * Queue the deletion of a locale's translation; it is deleted on save.
     */
    public function forgetTranslation(string $locale): static
    {
        unset($this->pendingTranslations[$locale]);

        if ($this->toLocaleStorage($locale) !== null) {
            $this->pendingTranslationDeletions[$locale] = true;
        }

        return $this;
    }

    /**
     * Persist queued translations and deletions.
     *
     * Called by Model::finishSave() before the original attributes are synced, so isDirty()
     * still tells whether the model itself changed. Translation rows keep their own timestamps;
     * when only translations changed, the model's updated_at is touched once.
     */
    public function savePendingTranslations(): void
    {
        if (! $this->exists) {
            return;
        }

        $changed = $this->deletePendingTranslations();

        if ($this->pendingTranslations !== []) {
            $foreignKey = $this->getTranslationForeignKey();
            $column = $this->getTranslationLocaleColumn();

            $this->getTranslationModel()::withoutTouching(function () use (&$changed, $foreignKey, $column) {
                foreach ($this->pendingTranslations as $locale => $translation) {
                    $translation->setAttribute($foreignKey, $this->getKey());

                    // A locale's row is reused, a soft-deleted one restored, so the
                    // unique(foreign key, locale) index holds.
                    if (! $translation->exists && ($existing = $translation->newQuery()
                        ->when($this->translationUsesSoftDeletes(), fn ($query) => $query->withTrashed())
                        ->where($foreignKey, $this->getKey())
                        ->where($column, $translation->getAttribute($column))
                        ->first())) {
                        $translation = $existing->fill($translation->getAttributes());

                        if ($this->translationUsesSoftDeletes() && $translation->trashed()) {
                            $translation->setAttribute($translation->getDeletedAtColumn(), null);
                        }
                    }

                    if (! $translation->exists || $translation->isDirty()) {
                        $translation->save();
                        $changed = true;
                    }

                    $this->resolvedTranslations[$locale] = $translation;
                }
            });

            $this->pendingTranslations = [];
        }

        if (! $changed) {
            return;
        }

        $this->unsetRelation('translations')->unsetRelation('localizedTranslations')->unsetRelation('translation');

        // A dirty model (or a fresh insert) already got a new updated_at from its own save.
        if (! $this->isDirty() && $this->usesTimestamps()) {
            $this->touch();
        }
    }

    /**
     * Delete the translations of locales queued with forgetTranslation() (or sent as null).
     *
     * @return bool Whether any translation was deleted.
     */
    protected function deletePendingTranslations(): bool
    {
        if ($this->pendingTranslationDeletions === []) {
            return false;
        }

        $locales = array_keys($this->pendingTranslationDeletions);
        $this->pendingTranslationDeletions = [];

        $deleted = $this->translations()
            ->whereIn($this->getTranslationLocaleColumn(), array_map(fn ($code) => $this->toLocaleStorage($code), $locales))
            ->delete();

        foreach ($locales as $locale) {
            $this->resolvedTranslations[$locale] = null;
        }

        return $deleted > 0;
    }

    /**
     * The queued translation model for a locale, created or loaded on first use.
     *
     * @throws InvalidArgumentException When the locale is not available.
     */
    protected function pendingTranslation(string $locale): Model
    {
        if (isset($this->pendingTranslations[$locale])) {
            return $this->pendingTranslations[$locale];
        }

        // Setting a value cancels a queued deletion of the same locale.
        unset($this->pendingTranslationDeletions[$locale]);

        $translation = $this->exists ? $this->translate($locale) : null;

        if (! $translation) {
            $storage = $this->toLocaleStorage($locale)
                ?? throw new InvalidArgumentException("Locale [{$locale}] is not available for translations.");

            $translation = $this->newTranslation();
            $translation->setAttribute($this->getTranslationLocaleColumn(), $storage);
        }

        return $this->pendingTranslations[$locale] = $translation;
    }

    /**
     * The value stored in the translation table for a locale code.
     */
    protected function toLocaleStorage(string $locale): string|int|null
    {
        return static::localeResolver()->toStorage($locale);
    }

    /**
     * The current and fallback locale codes.
     *
     * @return array<int, string>
     */
    protected function localizedCodes(): array
    {
        $resolver = static::localeResolver();

        return array_values(array_unique([$resolver->current(), $resolver->fallback()]));
    }

    /**
     * Scope to records having a translation matching the given criteria.
     */
    public function scopeWhereTranslation(Builder $query, string $column, mixed $value, ?string $locale = null): Builder
    {
        return $this->whereHasTranslation($query, $locale, fn ($q) => $q->where($column, $value));
    }

    /**
     * Scope to records having a translation like the given pattern.
     */
    public function scopeWhereTranslationLike(Builder $query, string $column, string $pattern, ?string $locale = null): Builder
    {
        return $this->whereHasTranslation($query, $locale, fn ($q) => $q->where($column, 'like', $pattern));
    }

    /**
     * Scope to order records by a translated column in a locale (default: the current one);
     * records without that translation sort as null.
     */
    public function scopeOrderByTranslation(Builder $query, string $column, string $direction = 'asc', ?string $locale = null): Builder
    {
        $translation = $this->newTranslation();

        $value = $translation->newQuery()
            ->select($translation->qualifyColumn($column))
            ->whereColumn($translation->qualifyColumn($this->getTranslationForeignKey()), $this->getQualifiedKeyName())
            ->where($translation->qualifyColumn($this->getTranslationLocaleColumn()), $this->toLocaleStorage($locale ?? static::localeResolver()->current()))
            ->limit(1);

        return $query->orderBy($value, $direction);
    }

    /**
     * Scope to eager-load translations: the current and fallback locales, or every locale
     * when another locale is requested.
     */
    public function scopeWithTranslation(Builder $query, ?string $locale = null): Builder
    {
        return $locale === null || in_array($locale, $this->localizedCodes(), true)
            ? $query->with('localizedTranslations')
            : $query->with('translations');
    }

    /**
     * Constrain a query to records having a translation in a locale that passes the callback.
     */
    protected function whereHasTranslation(Builder $query, ?string $locale, callable $callback): Builder
    {
        $storage = $this->toLocaleStorage($locale ?? static::localeResolver()->current());
        $column = $this->getTranslationLocaleColumn();

        return $query->whereHas('translations', function ($q) use ($callback, $column, $storage) {
            $callback($q);
            $q->where($column, $storage);
        });
    }
}
