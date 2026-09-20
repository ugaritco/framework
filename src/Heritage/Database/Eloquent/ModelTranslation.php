<?php

declare(strict_types=1);

namespace Heritage\Database\Eloquent;

use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Database\Eloquent\Relations\BelongsTo;
use Heritage\Support\Str;

/**
 * Class ModelTranslation
 *
 * Base abstract model for all multilingual translation entities in the Ugarit Ecosystem.
 *
 * Automatically provides:
 * - Disabled timestamps by default ($timestamps = false).
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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

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
        $localeModel = function_exists('config')
            ? config('i18n.models.locale', 'Ugarit\\Artifacts\\I18n\\Models\\Locale')
            : 'Ugarit\\Artifacts\\I18n\\Models\\Locale';

        if (class_exists($localeModel) && ($this->hasAttribute('locale_id') || in_array('locale_id', $this->getFillable(), true))) {
            return $this->belongsTo($localeModel, 'locale_id');
        }

        $code = $this->getAttribute('locale');

        if ($code !== null && class_exists('Ugarit\\Artifacts\\I18n\\Helpers\\LocaleHelper')) {
            $id = \Ugarit\Artifacts\I18n\Helpers\LocaleHelper::getLocaleId((string) $code);
            return $id !== null ? \Ugarit\Artifacts\I18n\Helpers\LocaleHelper::getLocaleById($id) : $code;
        }

        return $code;
    }
}
