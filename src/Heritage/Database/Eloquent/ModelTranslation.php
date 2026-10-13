<?php

declare(strict_types=1);

namespace Heritage\Database\Eloquent;

use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Database\Eloquent\Relations\BelongsTo;
use Heritage\Support\Str;
use Heritage\Translation\Locales;

/**
 * Class ModelTranslation
 *
 * Base abstract model for all multilingual translation entities in the Ugarit Ecosystem.
 *
 * Automatically provides:
 * - Its own timestamps (when the table has them); changes touch the parent's updated_at.
 * - Automatic translation table resolution (e.g. CityTranslation -> city_translations).
 * - Open mass-assignment for translated columns ($guarded = ['id']).
 * - Dynamic resolution of the parent model class and foreign key.
 * - Universal parent relationship (parent() as well as named method e.g. city(), country(), etc.).
 * - Dynamic property access for the parent relation ($cityTranslation->city).
 * - Decoupled locale resolution supporting both string code and numeric locale_id.
 */
abstract class ModelTranslation extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped. Translation rows keep their own
     * timestamps; they are maintained only when the table has the columns.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Changing a translation marks the parent model as updated.
     *
     * @var array<int, string>
     */
    protected $touches = ['parent'];

    /**
     * The column storing the locale; null uses the LocaleResolver's column
     * ("locale_id" with the i18n artifact, "locale" otherwise).
     *
     * @var string|null
     */
    public ?string $localeColumn = null;

    /**
     * Per-table cache of whether the table has timestamp columns.
     *
     * @var array<string, bool>
     */
    protected static array $tablesWithTimestamps = [];

    /**
     * Per-class cache of the parent's translatable attributes.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $parentTranslatable = [];

    /**
     * The column storing the locale in this translation table.
     */
    public function getLocaleColumn(): string
    {
        return $this->localeColumn ?: Locales::resolver()->storageColumn();
    }

    /**
     * The locale code of this translation (e.g. "ar"), whatever the storage.
     */
    public function getLocaleCode(): ?string
    {
        $value = $this->getAttributeFromArray($this->getLocaleColumn());

        return $value === null ? null : Locales::resolver()->fromStorage($value);
    }

    /**
     * The "locale_code" attribute ($translation->locale_code), for parcels and resources.
     */
    public function getLocaleCodeAttribute(): ?string
    {
        return $this->getLocaleCode();
    }

    /**
     * Touch the parent only when it maintains timestamps.
     *
     * @return array<int, string>
     */
    public function getTouchedRelations()
    {
        $parentClass = $this->getParentModelClass();

        return class_exists($parentClass) && (new $parentClass)->usesTimestamps()
            ? parent::getTouchedRelations()
            : [];
    }

    /**
     * Use timestamps only when enabled and the table actually has the columns.
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        if (! $this->timestamps) {
            return false;
        }

        $key = $this->getConnectionName().'|'.$this->getTable();

        return self::$tablesWithTimestamps[$key] ??= $this->getConnection()->getSchemaBuilder()
            ->hasColumns($this->getTable(), [static::CREATED_AT, static::UPDATED_AT]);
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * Get the fillable attributes for the model.
     *
     * Automatically merges the parent foreign key, locale_id, and locale
     * into the model's fillable attributes so they don't need to be manually
     * declared in child translation models.
     *
     * @return array<string>
     */
    public function getFillable()
    {
        $fillable = parent::getFillable();

        $defaults = [
            $this->getParentForeignKey(),
            'locale_id',
            'locale',
        ];

        $parentClass = $this->getParentModelClass();

        $translatable = self::$parentTranslatable[$parentClass] ??= class_exists($parentClass) && method_exists($parentClass, 'getTranslatable')
            ? (new $parentClass)->getTranslatable()
            : [];

        return array_values(array_unique(array_merge($defaults, $translatable, $fillable)));
    }

    /**
     * Get the table associated with the model.
     *
     * Automatically resolves convention: [ParentSnake]_translations
     * E.g. CityTranslation -> city_translations
     *
     * @return string
     */
    public function getTable()
    {
        if (! isset($this->table)) {
            $base = class_basename(static::class);
            if (str_ends_with($base, 'Translation')) {
                $parentName = Str::beforeLast($base, 'Translation');
                return Str::snake($parentName) . '_translations';
            }

            return Str::snake(Str::pluralStudly($base));
        }

        return $this->table;
    }

    /**
     * Get the parent model class name.
     *
     * @return class-string<\Heritage\Database\Eloquent\Model>
     */
    public function getParentModelClass(): string
    {
        if (property_exists($this, 'parentModel') && ! empty($this->parentModel)) {
            return $this->parentModel;
        }

        $namespace = Str::beforeLast(static::class, '\\');
        $parentBase = Str::beforeLast(class_basename(static::class), 'Translation');

        $candidate = $namespace . '\\' . $parentBase;
        if (class_exists($candidate)) {
            return $candidate;
        }

        return $parentBase;
    }

    /**
     * Get the parent foreign key name.
     *
     * @return string
     */
    public function getParentForeignKey(): string
    {
        if (property_exists($this, 'parentForeignKey') && ! empty($this->parentForeignKey)) {
            return $this->parentForeignKey;
        }

        $parentClass = $this->getParentModelClass();
        return Str::snake(class_basename($parentClass)) . '_id';
    }

    /**
     * Define the parent belonging relationship.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo($this->getParentModelClass(), $this->getParentForeignKey());
    }

    /**
     * Get the parent relationship method name (e.g. "city", "country").
     *
     * @return string
     */
    public function getParentMethodName(): string
    {
        return lcfirst(class_basename($this->getParentModelClass()));
    }

    /**
     * Determine if the given key is a relationship method on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function isRelation($key)
    {
        if ($key === $this->getParentMethodName() || $key === 'parent') {
            return true;
        }

        return parent::isRelation($key);
    }

    /**
     * Get a relationship value from a method.
     *
     * @param  string  $method
     * @return mixed
     */
    protected function getRelationshipFromMethod($method)
    {
        if ($method === $this->getParentMethodName()) {
            return $this->parent()->getResults();
        }

        return parent::getRelationshipFromMethod($method);
    }

    /**
     * Dynamically handle calls into the model to support named parent relationships.
     *
     * Allows calling $cityTranslation->city() instead of $cityTranslation->parent().
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method === $this->getParentMethodName()) {
            return $this->parent();
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Get the locale associated with this translation record.
     *
     * @return \Heritage\Database\Eloquent\Relations\BelongsTo|mixed
     */
    public function locale(): mixed
    {
        // With the i18n artifact, locale_id references its Locale model (config: i18n.models.locale).
        $localeModel = function_exists('config') ? config('i18n.models.locale') : null;

        if (is_string($localeModel) && class_exists($localeModel) && $this->getLocaleColumn() === 'locale_id') {
            return $this->belongsTo($localeModel, 'locale_id');
        }

        return $this->getLocaleCode();
    }
}
