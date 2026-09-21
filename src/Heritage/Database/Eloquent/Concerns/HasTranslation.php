<?php

declare(strict_types=1);

namespace Heritage\Database\Eloquent\Concerns;

use Heritage\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Relations\HasMany;
use Heritage\Database\Eloquent\Relations\HasOne;
use Heritage\Support\Str;
use Throwable;

/**
 * Trait providing native multilingual and translation capabilities for Eloquent Models.
 */
trait HasTranslation
{
    /**
     * Holds translation models queued for saving.
     *
     * @var array<string, \Heritage\Database\Eloquent\Model>
     */
    protected array $pendingTranslations = [];

    /**
     * Boot the translation trait for the model.
     */
    public static function bootHasTranslation(): void
    {
        static::saved(function (Model $model) {
            if ($model->isTranslatable()) {
                $model->savePendingTranslations();
            }
        });

        static::deleting(function (Model $model) {
            if ($model->isTranslatable()) {
                try {
                    $model->translations()->delete();
                } catch (Throwable) {
                    // Handled gracefully if already cascaded by database foreign key
                }
            }
        });
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
     * Get the fallback map for translatable attributes.
     *
     * @return array<string, string>
     */
    public function getTranslatableFallback(): array
    {
        return property_exists($this, 'translatableFallback') ? (array) $this->translatableFallback : [];
    }

    /**
     * Should translation be loaded on demand (lazy load).
     */
    public function isLoadTranslationOnDemand(): bool
    {
        return property_exists($this, 'loadTranslationOnDemand') ? (bool) $this->loadTranslationOnDemand : true;
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
        if (class_exists($conventional)) {
            return $conventional;
        }

        return $this->resolveDynamicTranslationModelClass();
    }

    /**
     * Resolve or dynamically generate a Translation Model class if none was manually defined.
     */
    protected function resolveDynamicTranslationModelClass(): string
    {
        $baseName = class_basename(static::class);
        $dynamicClassName = 'Heritage\\Database\\Eloquent\\DynamicTranslations\\'.$baseName.'Translation';

        if (! class_exists($dynamicClassName)) {
            $tableName = $this->getTranslationTable();
            eval("namespace Heritage\\Database\\Eloquent\\DynamicTranslations; class {$baseName}Translation extends \\Heritage\\Database\\Eloquent\\Model { public \$timestamps = false; protected \$table = '{$tableName}'; protected \$guarded = []; }");
        }

        return $dynamicClassName;
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
     * Define a hasMany relationship to get all translations for this model.
     *
     * @return \Heritage\Database\Eloquent\Relations\HasMany
     */
    public function translations(): HasMany
    {
        return $this->hasMany($this->getTranslationModel(), $this->getTranslationForeignKey());
    }

    /**
     * Define a hasOne relationship to get the translation for the given or current locale.
     *
     * @param  string|null  $locale
     * @return \Heritage\Database\Eloquent\Relations\HasOne
     */
    public function translation(?string $locale = null): HasOne
    {
        $currentLocale = $locale ?? $this->resolveCurrentLocale();
        $related = $this->getTranslationModel();
        $foreignKey = $this->getTranslationForeignKey();

        $relation = $this->hasOne($related, $foreignKey);
        $localeColumn = $this->resolveLocaleColumn($relation->getRelated());

        if ($localeColumn === 'locale_id') {
            $localeId = $this->resolveLocaleId($currentLocale);
            return $relation->where($localeColumn, $localeId);
        }

        return $relation->where($localeColumn, $currentLocale);
    }

    /**
     * Get the translated model instance for the given or active locale.
     */
    public function translate(?string $locale = null): ?Model
    {
        $locale = $locale ?? $this->resolveCurrentLocale();

        if (isset($this->pendingTranslations[$locale])) {
            return $this->pendingTranslations[$locale];
        }

        if ($this->relationLoaded('translation')) {
            $loaded = $this->getRelation('translation');
            if ($loaded) {
                $localeColumn = $this->resolveLocaleColumn($loaded);
                $expected = $localeColumn === 'locale_id' ? $this->resolveLocaleId($locale) : $locale;
                if ($loaded->{$localeColumn} == $expected) {
                    return $loaded;
                }
            }
        }

        if ($this->relationLoaded('translations')) {
            $localeColumn = 'locale';
            return $this->translations->first(function ($t) use ($locale, $localeColumn) {
                return ($t->{$localeColumn} ?? null) === $locale;
            });
        }

        if ($this->exists && $this->isLoadTranslationOnDemand()) {
            $translation = $this->translation($locale)->first();
            if ($locale === $this->resolveCurrentLocale()) {
                $this->setRelation('translation', $translation);
            }
            return $translation;
        }

        return null;
    }

    /**
     * Get the translated model instance or fallback to default locale.
     */
    public function translateOrDefault(?string $locale = null): ?Model
    {
        $translation = $this->translate($locale);
        if ($translation) {
            return $translation;
        }

        $fallbackLocale = function_exists('config') ? config('app.fallback_locale', 'en') : 'en';
        if ($fallbackLocale !== ($locale ?? $this->resolveCurrentLocale())) {
            return $this->translate($fallbackLocale);
        }

        return null;
    }

    /**
     * Check if a translation exists for the given or active locale.
     */
    public function hasTranslation(?string $locale = null): bool
    {
        return $this->translate($locale) !== null;
    }

    /**
     * Get a translated attribute value.
     */
    public function getTranslatedAttribute(string $key, ?string $locale = null): mixed
    {
        $translation = $this->translate($locale);

        if ($translation && isset($translation->{$key}) && $translation->{$key} !== '') {
            return $translation->{$key};
        }

        return null;
    }

    /**
     * Get the fallback value for a translatable attribute.
     */
    public function getTranslatableFallbackValue(string $key): mixed
    {
        $fallbackMap = $this->getTranslatableFallback();

        if (isset($fallbackMap[$key])) {
            $fallbackColumn = $fallbackMap[$key];
            if ($this->hasAttribute($fallbackColumn)) {
                return $this->getAttributeFromArray($fallbackColumn);
            }
        }

        $fallbackLocale = function_exists('config') ? config('app.fallback_locale', 'en') : 'en';
        if ($fallbackLocale !== $this->resolveCurrentLocale()) {
            $fallbackTranslation = $this->translate($fallbackLocale);
            if ($fallbackTranslation && isset($fallbackTranslation->{$key}) && $fallbackTranslation->{$key} !== '') {
                return $fallbackTranslation->{$key};
            }
        }

        return null;
    }

    /**
     * Set a translation value for a given attribute.
     */
    public function setTranslation(string $key, mixed $value, ?string $locale = null): static
    {
        $locale = $locale ?? $this->resolveCurrentLocale();

        if (! isset($this->pendingTranslations[$locale])) {
            $instance = $this->exists ? $this->translation($locale)->first() : null;
            if (! $instance) {
                $class = $this->getTranslationModel();
                $instance = new $class;
                $localeColumn = $this->resolveLocaleColumn($instance);
                if ($localeColumn === 'locale_id') {
                    $instance->{$localeColumn} = $this->resolveLocaleId($locale);
                } else {
                    $instance->{$localeColumn} = $locale;
                }
            }
            $this->pendingTranslations[$locale] = $instance;
        }

        $this->pendingTranslations[$locale]->{$key} = $value;

        if ($locale === $this->resolveCurrentLocale() && $this->relationLoaded('translation')) {
            $loaded = $this->getRelation('translation');
            if ($loaded) {
                $loaded->{$key} = $value;
            } else {
                $this->setRelation('translation', $this->pendingTranslations[$locale]);
            }
        }

        return $this;
    }

    /**
     * Fill multiple translations for a specific locale.
     *
     * @param  string  $locale
     * @param  array<string, mixed>  $attributes
     * @return $this
     */
    public function fillTranslation(string $locale, array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setTranslation($key, $value, $locale);
        }

        return $this;
    }

    /**
     * Save any translations queued in pending state.
     */
    public function savePendingTranslations(): void
    {
        if (empty($this->pendingTranslations)) {
            return;
        }

        $foreignKey = $this->getTranslationForeignKey();
        $id = $this->getKey();

        foreach ($this->pendingTranslations as $translation) {
            $translation->{$foreignKey} = $id;
            $translation->save();
        }

        $this->pendingTranslations = [];
    }

    /**
     * Resolve the active application locale.
     */
    protected function resolveCurrentLocale(): string
    {
        if (function_exists('app') && app()->bound('translator')) {
            return app()->getLocale();
        }

        return 'ar';
    }

    /**
     * Resolve the column used to store the locale in the translation table.
     */
    protected function resolveLocaleColumn(Model $related): string
    {
        if (property_exists($related, 'localeColumn') && ! empty($related->localeColumn)) {
            return $related->localeColumn;
        }

        return 'locale';
    }

    /**
     * Resolve numeric ID for a locale code if using locale_id.
     */
    protected function resolveLocaleId(string $localeCode): ?int
    {
        static $cachedLocaleIds = [];

        if (array_key_exists($localeCode, $cachedLocaleIds)) {
            return $cachedLocaleIds[$localeCode];
        }

        if (class_exists(\Ugarit\Artifacts\I18n\Models\Locale::class)) {
            try {
                $id = \Ugarit\Artifacts\I18n\Models\Locale::where('code', $localeCode)->value('id');
                return $cachedLocaleIds[$localeCode] = $id ? (int) $id : null;
            } catch (Throwable) {
                // Return null if table not yet migrated
            }
        }

        return $cachedLocaleIds[$localeCode] = null;
    }

    /**
     * Scope a query to records having a translation matching the given criteria.
     *
     * @param  \Heritage\Database\Eloquent\Builder  $query
     * @param  string  $column
     * @param  mixed  $value
     * @param  string|null  $locale
     * @return \Heritage\Database\Eloquent\Builder
     */
    public function scopeWhereTranslation(Builder $query, string $column, mixed $value, ?string $locale = null): Builder
    {
        $locale = $locale ?? $this->resolveCurrentLocale();
        $related = $this->getTranslationModel();
        $instance = new $related;
        $localeColumn = $this->resolveLocaleColumn($instance);

        return $query->whereHas('translations', function ($q) use ($column, $value, $locale, $localeColumn) {
            $q->where($column, $value);
            if ($localeColumn === 'locale_id') {
                $q->where($localeColumn, $this->resolveLocaleId($locale));
            } else {
                $q->where($localeColumn, $locale);
            }
        });
    }

    /**
     * Scope a query to records having a translation like the given pattern.
     *
     * @param  \Heritage\Database\Eloquent\Builder  $query
     * @param  string  $column
     * @param  string  $pattern
     * @param  string|null  $locale
     * @return \Heritage\Database\Eloquent\Builder
     */
    public function scopeWhereTranslationLike(Builder $query, string $column, string $pattern, ?string $locale = null): Builder
    {
        $locale = $locale ?? $this->resolveCurrentLocale();
        $related = $this->getTranslationModel();
        $instance = new $related;
        $localeColumn = $this->resolveLocaleColumn($instance);

        return $query->whereHas('translations', function ($q) use ($column, $pattern, $locale, $localeColumn) {
            $q->where($column, 'like', $pattern);
            if ($localeColumn === 'locale_id') {
                $q->where($localeColumn, $this->resolveLocaleId($locale));
            } else {
                $q->where($localeColumn, $locale);
            }
        });
    }

    /**
     * Scope a query to eager load translations.
     *
     * @param  \Heritage\Database\Eloquent\Builder  $query
     * @param  string|null  $locale
     * @return \Heritage\Database\Eloquent\Builder
     */
    public function scopeWithTranslation(Builder $query, ?string $locale = null): Builder
    {
        return $query->with(['translation' => function ($q) use ($locale) {
            if ($locale) {
                $instance = $q->getModel();
                $localeColumn = $this->resolveLocaleColumn($instance);
                $q->where($localeColumn, $locale);
            }
        }]);
    }
}
