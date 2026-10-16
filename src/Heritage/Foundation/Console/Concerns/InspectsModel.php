<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Concerns;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Relations\BelongsTo;
use Heritage\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Reads what the domain generators need from an existing model: its fillable columns with their
 * PHP types (from $casts, or "_id" for foreign keys), the models its foreign keys refer to, and its
 * translatable attributes, which travel in a `translations` payload instead of columns.
 */
trait InspectsModel
{
    /**
     * Resolve the --model option to a model class: as given when it is an existing model, otherwise
     * under the root namespace's Models folder (so "Locale" is not PHP's global \Locale class).
     *
     * @return class-string|null
     */
    protected function modelClass(): ?string
    {
        $model = $this->hasOption('model') ? $this->option('model') : null;

        if (! is_string($model) || trim($model) === '') {
            return null;
        }

        $model = str_replace('/', '\\', trim($model, '\\/'));

        return is_subclass_of($model, Model::class) ? $model : $this->qualifyModel($model);
    }

    /**
     * The model's fillable columns (translatable attributes excluded) with their PHP types.
     *
     * @param  string|null  $model  The model class; nothing is read when it does not exist yet.
     * @return array<string, string> Column => "int", "bool", "float", "array" or "string".
     */
    protected function modelColumns(?string $model): array
    {
        $instance = $this->modelInstance($model);

        if ($instance === null) {
            return [];
        }

        $translatable = $this->modelTranslatable($model);
        $casts = $instance->getCasts();
        $columns = [];

        foreach ($instance->getFillable() as $column) {
            if (! in_array($column, $translatable, true)) {
                $columns[$column] = $this->castType($casts[$column] ?? null, $column);
            }
        }

        return $columns;
    }

    /**
     * The model's translatable attributes.
     *
     * @param  string|null  $model
     * @return array<int, string>
     */
    protected function modelTranslatable(?string $model): array
    {
        $instance = $this->modelInstance($model);

        return $instance !== null && method_exists($instance, 'getTranslatable') ? $instance->getTranslatable() : [];
    }

    /**
     * The PHP type of a column from its cast.
     *
     * @param  mixed  $cast
     * @param  string  $column
     * @return string
     */
    protected function castType(mixed $cast, string $column): string
    {
        $cast = is_string($cast) ? Str::before(strtolower($cast), ':') : null;

        return match (true) {
            in_array($cast, ['int', 'integer', 'timestamp'], true) => 'int',
            in_array($cast, ['bool', 'boolean'], true) => 'bool',
            in_array($cast, ['float', 'double', 'real', 'decimal'], true) => 'float',
            in_array($cast, ['array', 'json', 'object', 'collection'], true) => 'array',
            $cast === null && str_ends_with($column, '_id') => 'int',
            default => 'string',
        };
    }

    /**
     * The model a foreign key refers to: the related model of the belongsTo() relation it names
     * ("country_id" names country(), whose model may be in another artifact), else the model of
     * that name in the model's folder ("branch_id" names Branch).
     *
     * @param  \Heritage\Database\Eloquent\Model  $instance
     * @param  string  $column  The foreign key, e.g. "country_id".
     * @return class-string<\Heritage\Database\Eloquent\Model>|null Null when no such model exists.
     */
    protected function relatedModelOf(object $instance, string $column): ?string
    {
        if (! str_ends_with($column, '_id')) {
            return null;
        }

        $relation = Str::camel(Str::beforeLast($column, '_id'));

        if (method_exists($instance, $relation)) {
            $type = (new ReflectionMethod($instance, $relation))->getReturnType();

            if ($type instanceof ReflectionNamedType && is_a($type->getName(), BelongsTo::class, true)) {
                try {
                    return $instance->{$relation}()->getRelated()::class;
                } catch (Throwable) {
                    return null;
                }
            }
        }

        $related = Str::beforeLast($instance::class, '\\').'\\'.Str::studly($relation);

        return is_subclass_of($related, Model::class) ? $related : null;
    }

    /**
     * A fresh instance of the model, or null when the class cannot be loaded.
     *
     * @param  string|null  $model
     * @return \Heritage\Database\Eloquent\Model|null
     */
    protected function modelInstance(?string $model): ?object
    {
        if ($model === null || ! class_exists($model)) {
            return null;
        }

        try {
            return new $model;
        } catch (Throwable) {
            return null;
        }
    }
}
