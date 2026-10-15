<?php

declare(strict_types=1);

namespace Heritage\Foundation\Support;

use FilesystemIterator;
use Heritage\Contracts\Support\Arrayable;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\QueryException;
use Heritage\Foundation\Support\Providers\ArtifactResourcesServiceProvider;
use Heritage\Support\Carbon;
use Heritage\Support\Facades\Route;
use Heritage\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * The summary of a loaded artifact, as its panel summary page shows it, read from the artifact
 * itself by convention:
 *
 * | Key        | Read from                                                                 |
 * | :--------- | :------------------------------------------------------------------------ |
 * | `artifact` | its `art.php` manifest: id, name, description, version, requires, provides |
 * | `stats`    | the totals of its entities, records, translations, capabilities, features |
 * | `entities` | a model each (`src/Models`, translation models aside): its records, the capabilities and features in `src/Capabilities/{Model}` and `src/Features/{Model}`, and its `{model}.index` route; `available` is false while its table does not match it |
 * | `recent`   | its latest updated records, across its models                             |
 */
class ArtifactSummary implements Arrayable, JsonSerializable
{
    /**
     * The number of latest records listed.
     */
    public const RECENT = 8;

    /**
     * The attributes naming a record, in order of preference.
     *
     * @var array<int, string>
     */
    protected const LABELS = ['name', 'title', 'label', 'full_name', 'email', 'slug', 'code'];

    /**
     * Create the summary of an artifact.
     *
     * @param  string  $id  The artifact id.
     * @param  string  $path  The absolute root path of the artifact.
     */
    public function __construct(protected string $id, protected string $path)
    {
        $this->path = rtrim($path, '/\\');
    }

    /**
     * The summary of a loaded artifact.
     *
     * @param  string  $id  The artifact id.
     * @return static
     *
     * @throws InvalidArgumentException When the artifact is not loaded.
     */
    public static function of(string $id): static
    {
        $provider = app()->getProvider(ArtifactResourcesServiceProvider::class);
        $path = $provider instanceof ArtifactResourcesServiceProvider ? ($provider->artifacts()[$id] ?? null) : null;

        if ($path === null) {
            throw new InvalidArgumentException("The artifact [{$id}] is not loaded.");
        }

        return new static($id, $path);
    }

    /**
     * The summary as an array.
     *
     * @return array{artifact: array<string, mixed>, stats: array<string, int>, entities: array<int, array<string, mixed>>, recent: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        $models = $this->models();
        $entities = array_map(fn (string $model) => $this->entity($model), $models);

        return [
            'artifact' => $this->manifest(),
            'stats' => [
                'entities' => count($entities),
                'records' => array_sum(array_column($entities, 'records')),
                'created_this_month' => array_sum(array_column($entities, 'created_this_month')),
                'translatable' => count(array_filter(array_column($entities, 'translatable'))),
                'translations' => array_sum(array_column($entities, 'translations')),
                'capabilities' => count($this->classes('Capabilities', 'Capability', true)),
                'features' => count($this->classes('Features', 'Feature', true)),
            ],
            'entities' => $entities,
            'recent' => $this->recent($models),
        ];
    }

    /**
     * The summary serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * The identity of the artifact, read from its manifest.
     *
     * @return array{id: string, name: string, description: string, version: string|null, requires: array<int, string>, provides: array<int, array{slug: string, description: string}>}
     */
    protected function manifest(): array
    {
        $art = is_file($file = $this->path.'/art.php') ? require $file : null;
        $art = is_object($art) ? $art : null;
        $provides = (array) ($art->capabilities ?? []);

        return [
            'id' => $this->id,
            'name' => (string) ($art->name ?? Str::headline($this->id)),
            'description' => (string) ($art->description ?? ''),
            'version' => isset($art->version) ? (string) $art->version : null,
            'requires' => array_values(array_map('strval', (array) ($art->requires ?? []))),
            'provides' => array_map(
                fn (string $slug) => ['slug' => $slug, 'description' => (string) $provides[$slug]],
                array_map('strval', array_keys($provides))
            ),
        ];
    }

    /**
     * The model classes of the artifact, by name; a translation model belongs to its model.
     *
     * @return array<int, class-string<Model>>
     */
    protected function models(): array
    {
        $directory = $this->path.'/src/Models';

        if (! is_dir($directory)) {
            return [];
        }

        $models = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory) + 1, -4);
            $class = $this->namespace().'\\Models\\'.str_replace('/', '\\', $relative);

            if ($this->isEntity($class)) {
                $models[] = $class;
            }
        }

        sort($models);

        return $models;
    }

    /**
     * Whether a class is a concrete model other than a translation model.
     *
     * @param  string  $class
     * @return bool
     */
    protected function isEntity(string $class): bool
    {
        if (! class_exists($class) || ! is_subclass_of($class, Model::class) || (new ReflectionClass($class))->isAbstract()) {
            return false;
        }

        return ! (str_ends_with($class, 'Translation') && class_exists(Str::beforeLast($class, 'Translation')));
    }

    /**
     * The summary of a model: its records, translations, capabilities, features and listing.
     *
     * @param  class-string<Model>  $class
     * @return array<string, mixed>
     */
    protected function entity(string $class): array
    {
        $model = new $class;
        $name = class_basename($class);
        $route = Str::kebab($name);
        $translatable = method_exists($model, 'isTranslatable') && $model->isTranslatable();

        return [
            'model' => $name,
            'key' => $route,
            'lang' => Str::snake($name),
            'table' => $model->getTable(),
            ...$this->figures($model, $translatable),
            'translatable' => $translatable,
            'capabilities' => $this->classes("Capabilities/{$name}", 'Capability'),
            'features' => $this->classes("Features/{$name}", 'Feature'),
            'url' => Route::has("{$route}.index") ? route("{$route}.index", absolute: false) : null,
        ];
    }

    /**
     * The figures of a model's records; a model whose table does not match it yet (its migration
     * not run) is reported unavailable, with no figures, rather than failing the summary.
     *
     * @param  Model  $model
     * @param  bool  $translatable
     * @return array{available: bool, records: int, created_this_month: int, active: int|null, trashed: int|null, translations: int, last_updated_at: string|null}
     */
    protected function figures(Model $model, bool $translatable): array
    {
        $createdAt = $model->usesTimestamps() ? $model->getCreatedAtColumn() : null;
        $updatedAt = $model->usesTimestamps() ? $model->getUpdatedAtColumn() : null;

        try {
            $lastUpdate = $updatedAt ? $model->newQuery()->max($updatedAt) : null;

            return [
                'available' => true,
                'records' => $model->newQuery()->count(),
                'created_this_month' => $createdAt ? $model->newQuery()->where($createdAt, '>=', Carbon::now()->startOfMonth())->count() : 0,
                'active' => in_array('active', $model->getFillable(), true) ? $model->newQuery()->where('active', true)->count() : null,
                'trashed' => method_exists($model, 'getDeletedAtColumn') ? $model->newQuery()->onlyTrashed()->count() : null,
                'translations' => $translatable ? $model->translations()->getRelated()->newQuery()->count() : 0,
                'last_updated_at' => $lastUpdate ? Carbon::parse($lastUpdate)->toIso8601String() : null,
            ];
        } catch (QueryException) {
            return [
                'available' => false,
                'records' => 0,
                'created_this_month' => 0,
                'active' => null,
                'trashed' => null,
                'translations' => 0,
                'last_updated_at' => null,
            ];
        }
    }

    /**
     * The latest updated records of the given models, newest first.
     *
     * @param  array<int, class-string<Model>>  $models
     * @return array<int, array{entity: string, lang: string, id: mixed, label: string, url: string|null, created: bool, updated_at: string}>
     */
    protected function recent(array $models): array
    {
        $records = [];

        foreach ($models as $class) {
            $model = new $class;

            if (! $model->usesTimestamps()) {
                continue;
            }

            $route = Str::kebab(class_basename($class));
            $show = Route::has("{$route}.show");

            try {
                $latest = $class::query()->latest($model->getUpdatedAtColumn())->limit(static::RECENT)->get();
            } catch (QueryException) {
                continue;
            }

            foreach ($latest as $record) {
                $updated = $record->getAttribute($model->getUpdatedAtColumn());
                $created = $record->getAttribute($model->getCreatedAtColumn());

                if ($updated === null) {
                    continue;
                }

                $records[] = [
                    'entity' => $route,
                    'lang' => Str::snake(class_basename($class)),
                    'id' => $record->getKey(),
                    'label' => $this->label($record),
                    'url' => $show ? route("{$route}.show", $record, false) : null,
                    'created' => $created !== null && Carbon::parse($created)->equalTo(Carbon::parse($updated)),
                    'updated_at' => Carbon::parse($updated)->toIso8601String(),
                ];
            }
        }

        usort($records, fn (array $a, array $b) => strcmp($b['updated_at'], $a['updated_at']));

        return array_slice($records, 0, static::RECENT);
    }

    /**
     * The name of a record: its first filled naming attribute (translated in the current locale),
     * else its key.
     *
     * @param  Model  $record
     * @return string
     */
    protected function label(Model $record): string
    {
        foreach (static::LABELS as $attribute) {
            $value = match (true) {
                method_exists($record, 'isTranslatableAttribute') && $record->isTranslatableAttribute($attribute) => $record->getTranslatedAttribute($attribute),
                array_key_exists($attribute, $record->getAttributes()) => $record->getAttribute($attribute),
                default => null,
            };

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return '#'.$record->getKey();
    }

    /**
     * The short names of the classes in a directory of the artifact's src, without their suffix.
     *
     * @param  string  $directory  Relative to src (e.g. "Capabilities/Genre").
     * @param  string  $suffix  The suffix of the class names (e.g. "Capability").
     * @param  bool  $recursive  Whether the subdirectories are read too.
     * @return array<int, string>
     */
    protected function classes(string $directory, string $suffix, bool $recursive = false): array
    {
        $path = $this->path.'/src/'.$directory;

        if (! is_dir($path)) {
            return [];
        }

        $files = $recursive
            ? iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)), false)
            : iterator_to_array(new FilesystemIterator($path), false);
        $names = [];

        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), "{$suffix}.php")) {
                $names[] = substr($file->getFilename(), 0, -strlen("{$suffix}.php"));
            }
        }

        sort($names);

        return $names;
    }

    /**
     * The root namespace of the artifact: its manifest's, else Ugarit\Artifacts\{Studly}.
     *
     * @return string
     */
    protected function namespace(): string
    {
        $manifest = $this->path.'/art.php';

        if (is_file($manifest) && preg_match('/^namespace\s+([^;]+);/m', (string) file_get_contents($manifest), $match)) {
            return trim($match[1], '\\ ');
        }

        return 'Ugarit\\Artifacts\\'.Str::studly($this->id);
    }
}
