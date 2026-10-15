<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Closure;
use Heritage\Console\GeneratorCommand;
use Heritage\Database\Console\Field;
use Heritage\Foundation\Console\Concerns\EditsSource;
use Heritage\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * Adds fields to an existing model and to every file of its slice: the model ($fillable or
 * $translatable, $casts, a belongsTo() per foreign key), a migration adding the columns, the
 * factory, the Store/Update requests, the Save and Index parcels, the resource and the lang files.
 *
 * Files are edited in place, so the code written in them is kept; a file or a place that cannot
 * be found is reported instead.
 */
#[AsCommand(name: 'make:field')]
class FieldMakeCommand extends GeneratorCommand
{
    use EditsSource;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:field
                    {model : The model receiving the fields (e.g. Department)}
                    {fields* : The fields, as name:type[:modifier...] (e.g. description:text:nullable branch_id:foreignId)}
                    {--artifact= : The target modular artifact name}
                    {--without-migration : Do not create the migration adding the columns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add fields to a model and to every file of its capabilities slice';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Field';

    /**
     * The status of each file, by path.
     *
     * @var array<string, string>
     */
    protected array $report = [];

    /**
     * The places that could not be found, as "path: place".
     *
     * @var array<int, string>
     */
    protected array $misses = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->promptForArtifact('Which artifact does the model belong to?');

        $model = Str::studly(class_basename(str_replace('/', '\\', (string) $this->argument('model'))));
        $modelClass = $this->qualifyModel($model);

        if (! class_exists($modelClass)) {
            $this->components->error("The model [{$modelClass}] does not exist.");

            return self::FAILURE;
        }

        $instance = new $modelClass;
        $fields = Field::parseMany(implode(',', (array) $this->argument('fields')));

        foreach ($fields as $field) {
            if (in_array($field->name, [...$instance->getFillable(), ...$instance->getTranslatable()], true)) {
                $this->components->error("[{$model}] already has the field [{$field->name}].");

                return self::FAILURE;
            }

            if ($field->has('translatable') && $instance->getTranslatable() === []) {
                $this->components->error("[{$model}] is not translatable, so [{$field->name}] cannot be: it has no translation table.");

                return self::FAILURE;
            }
        }

        $this->editModel($modelClass, $fields);

        if (! $this->option('without-migration')) {
            $this->createMigration($instance, $fields);
        }

        $this->editFactory($model, $modelClass, $fields);
        $this->editRequests($model, $modelClass, $fields);
        $this->editParcels($model, $fields);
        $this->editResource($model, $fields);
        $this->editTranslation($model, $fields);
        $this->editLang($model, $fields);

        foreach ($this->report as $path => $status) {
            $this->components->twoColumnDetail(str_replace('\\', '/', str_replace($this->ugarit->basePath().DIRECTORY_SEPARATOR, '', $path)), $status);
        }

        foreach ($this->misses as $miss) {
            $this->components->warn("Not found, add it by hand: {$miss}");
        }

        if ((glob($this->ugarit->langPath('*.json')) ?: []) !== []) {
            $this->call('lang:sync');
        }

        return self::SUCCESS;
    }

    /**
     * Add the fields to the model: $fillable or $translatable, $casts and a belongsTo() per foreign key,
     * with its hasMany() inverse on the related model.
     *
     * @param  string  $modelClass
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function editModel(string $modelClass, array $fields): void
    {
        $path = (new ReflectionClass($modelClass))->getFileName();

        $this->edit($path, function (string $source) use ($path, $modelClass, $fields) {
            foreach ($fields as $field) {
                $source = $field->has('translatable')
                    ? $this->add($source, $path, '/protected (?:array )?\$translatable\s*=\s*\[/', "'{$field->name}'", '$translatable')
                    : $this->add($source, $path, '/protected (?:array )?\$fillable\s*=\s*\[/', "'{$field->name}'", '$fillable');

                if ($field->cast() !== null && ! $field->has('translatable')) {
                    $source = $this->addCast($source, $path, $field);
                }

                if ($field->relation() !== null && ! method_exists($modelClass, $field->relation())) {
                    $source = $this->addImport($this->addToClass($source, $field->belongsTo(class_basename($modelClass))) ?? $source, 'Heritage\\Database\\Eloquent\\Relations\\BelongsTo');
                }
            }

            return $source;
        });

        foreach ($fields as $field) {
            if (($related = $this->relatedModel($modelClass, $field)) !== null && $related !== $modelClass) {
                $this->edit(
                    (new ReflectionClass($related))->getFileName(),
                    fn (string $source) => $this->addHasMany($source, $field, class_basename($modelClass))
                );
            }
        }
    }

    /**
     * Add a field's cast to the $casts property, the casts() method, or a new $casts property.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  Field  $field
     * @return string
     */
    protected function addCast(string $source, string $path, Field $field): string
    {
        $item = "'{$field->name}' => '{$field->cast()}'";

        foreach (['/protected (?:array )?\$casts\s*=\s*\[/', '/function casts\(\)[^{]*\{\s*return\s*\[/'] as $opening) {
            if ($this->listContent($source, $opening) !== null) {
                return $this->add($source, $path, $opening, $item, '$casts');
            }
        }

        return $this->addToClass($source, <<<PHP
                /**
                 * Attribute casting definitions.
                 *
                 * @var array<string, string>
                 */
                protected \$casts = [
                    {$item},
                ];
            PHP) ?? $source;
    }

    /**
     * Create the migration adding the columns to the model's table; with a translatable field,
     * Schema::tableTranslatable() sends the translatable ones to its translation table.
     *
     * @param  \Heritage\Database\Eloquent\Model  $instance
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function createMigration(object $instance, array $fields): void
    {
        $method = array_filter($fields, fn (Field $field) => $field->has('translatable')) ? 'tableTranslatable' : 'table';

        $block = function (array $statements, string $separator) use ($instance, $method): string {
            $lines = implode($separator, array_map(fn ($statement) => preg_replace('/^/m', '            ', $statement), $statements));

            return "        Schema::{$method}('{$instance->getTable()}', function (Blueprint \$table) {\n{$lines}\n        });";
        };

        $names = count($fields) > 2 ? 'fields' : implode('_and_', array_map(fn (Field $field) => $field->name, $fields));
        $artifact = $this->getArtifactOption();
        $directory = $artifact ? $this->ugarit->basePath("artifacts/{$artifact}/database/migrations") : $this->ugarit->databasePath('migrations');

        $path = $this->ugarit->make('migration.creator')->createWithBody(
            "add_{$names}_to_{$instance->getTable()}_table",
            $directory,
            $block(array_map(fn (Field $field) => $field->comment()."\n".$field->addedColumn(), $fields), "\n\n"),
            $block(array_merge(...array_map(fn (Field $field) => $field->droppedColumn(), array_reverse($fields))), "\n")
        );

        $this->report[$path] = '<fg=green>created</>';
    }

    /**
     * Add the fields to the factory's definition(); a foreign key creates its related model.
     *
     * @param  string  $model
     * @param  string  $modelClass
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function editFactory(string $model, string $modelClass, array $fields): void
    {
        $artifact = $this->getArtifactOption();
        $path = $artifact
            ? $this->ugarit->basePath("artifacts/{$artifact}/database/factories/{$model}Factory.php")
            : $this->ugarit->databasePath("factories/{$model}Factory.php");

        $this->edit($path, function (string $source) use ($path, $modelClass, $fields) {
            foreach ($fields as $field) {
                $value = Field::fake($field->name, $field->has('translatable') ? null : $field->cast());

                if (($related = $this->relatedModel($modelClass, $field)) !== null) {
                    $source = $this->addImport($source, $related);
                    $value = class_basename($related).'::factory()';
                }

                $source = $this->add($source, $path, '/function definition\(\)[^{]*\{\s*return\s*\[/', "'{$field->name}' => {$value}", 'definition()');
            }

            return $source;
        });
    }

    /**
     * Add the fields' rules to the Store and Update requests, and the translatable fields to their $translatable.
     *
     * @param  string  $model
     * @param  string  $modelClass
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function editRequests(string $model, string $modelClass, array $fields): void
    {
        foreach (['Store', 'Update'] as $operation) {
            $path = $this->srcPath("Http/Requests/{$model}/Capabilities/{$operation}{$model}Request");

            $this->edit($path, function (string $source) use ($path, $modelClass, $fields) {
                foreach ($fields as $field) {
                    $related = $this->relatedModel($modelClass, $field);
                    $rule = $field->rule($related ? $this->tableOf($related) : null);

                    $source = $this->add($source, $path, '/function rules\(\)[^{]*\{\s*return\s*\[/', "'{$field->name}' => '{$rule}'", 'rules()');

                    if ($field->has('translatable')) {
                        $source = $this->add($source, $path, '/protected (?:array )?\$translatable\s*=\s*\[/', "'{$field->name}'", '$translatable (the request is not translatable)');
                    }
                }

                return $source;
            });
        }
    }

    /**
     * Add the fields to the Save parcel's constructor, and to the Index parcel's filters
     * (booleans and foreign keys) or its searchable and sortable columns (texts).
     *
     * @param  string  $model
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function editParcels(string $model, array $fields): void
    {
        $save = $this->srcPath("Parcels/{$model}/Capabilities/Save{$model}Parcel");

        $this->edit($save, function (string $source) use ($save, $fields) {
            foreach ($fields as $field) {
                if (! $field->has('translatable')) {
                    $source = $this->addParameter($source, $save, $field, "The {$field->name} column.");
                }
            }

            return $source;
        });

        $index = $this->srcPath("Parcels/{$model}/Capabilities/Index{$model}Parcel");

        $this->edit($index, function (string $source) use ($index, $fields) {
            foreach ($fields as $field) {
                if ($field->type === 'boolean' || $field->type === 'foreignId') {
                    $source = $this->addParameter($source, $index, $field, "Only the records with this {$field->name}.");
                } elseif (in_array($field->type, ['string', 'text'], true)) {
                    $source = $this->add($source, $index, '/function searchable\(\)[^{]*\{\s*return\s*\[/', "'{$field->name}'", 'searchable()');
                    $source = $this->add($source, $index, '/function sortable\(\)[^{]*\{\s*return\s*\[/', "'{$field->name}'", 'sortable()', "'created_at'");
                }
            }

            return $source;
        });
    }

    /**
     * Add the fields to the resource, before the timestamps.
     *
     * @param  string  $model
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function editResource(string $model, array $fields): void
    {
        $path = $this->srcPath("Http/Resources/{$model}/{$model}Resource");

        $this->edit($path, function (string $source) use ($path, $fields) {
            foreach ($fields as $field) {
                $source = $this->add($source, $path, '/function toArray\([^)]*\)[^{]*\{\s*return\s*\[/', "'{$field->name}' => \$this->{$field->name}", 'toArray()', "'created_at'");
            }

            return $source;
        });
    }

    /**
     * Add the translatable fields to the translations slice, when the model has one: the Save
     * translation parcel, the Store and Update translation requests and the translation resource.
     *
     * @param  string  $model
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function editTranslation(string $model, array $fields): void
    {
        $fields = array_filter($fields, fn (Field $field) => $field->has('translatable'));
        $parcel = $this->srcPath("Parcels/{$model}/Capabilities/Translation/Save{$model}TranslationParcel");

        if ($fields === [] || ! $this->files->exists($parcel)) {
            return;
        }

        $this->edit($parcel, function (string $source) use ($parcel, $fields) {
            foreach ($fields as $field) {
                $source = $this->addParameter($source, $parcel, $field, 'Translated '.str_replace('_', ' ', $field->name).'.');
            }

            return $source;
        });

        foreach (['Store', 'Update'] as $operation) {
            $path = $this->srcPath("Http/Requests/{$model}/Capabilities/Translation/{$operation}{$model}TranslationRequest");

            $this->edit($path, function (string $source) use ($path, $fields) {
                foreach ($fields as $field) {
                    $source = $this->add($source, $path, '/function rules\(\)[^{]*\{[^{}]*?return\s*\[/', "'{$field->name}' => '{$field->rule(null)}'", 'rules()');
                }

                return $source;
            });
        }

        $resource = $this->srcPath("Http/Resources/{$model}/Translation/{$model}TranslationResource");

        $this->edit($resource, function (string $source) use ($resource, $fields) {
            foreach ($fields as $field) {
                $source = $this->add($source, $resource, '/function toArray\([^)]*\)[^{]*\{\s*return\s*\[/', "'{$field->name}' => \$this->{$field->name}", 'toArray()', "'created_at'");
            }

            return $source;
        });
    }

    /**
     * Add the fields' label and placeholder to the model's lang file of every locale.
     *
     * @param  string  $model
     * @param  array<int, Field>  $fields
     * @return void
     */
    protected function editLang(string $model, array $fields): void
    {
        $artifact = $this->getArtifactOption();
        $file = Str::snake($model).'.php';
        $paths = glob(($artifact ? $this->ugarit->basePath("artifacts/{$artifact}/lang") : $this->ugarit->langPath())."/*/{$file}") ?: [];

        foreach ($paths as $path) {
            $this->edit($path, function (string $source) use ($path, $fields) {
                foreach ($fields as $field) {
                    $label = Str::headline(preg_replace('/_id$/', '', $field->name));
                    $item = "'{$field->name}' => [\n    'label' => '{$label}',\n    'placeholder' => null,\n]";

                    $source = $this->add($source, $path, "/'attributes'\s*=>\s*\[/", $item, "'attributes'", "'created_at'");
                }

                return $source;
            });
        }
    }

    /**
     * Add a field to a parcel's constructor, with its @param line, before $translations when there is one.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  Field  $field
     * @param  string  $description
     * @return string
     */
    protected function addParameter(string $source, string $path, Field $field, string $description): string
    {
        $type = $field->phpType();
        $edited = $this->add($source, $path, '/function __construct\(/', "public ?{$type} \${$field->name} = null", '__construct()', '$translations');

        if ($edited === $source || ($constructor = strpos($edited, 'function __construct(')) === false) {
            return $edited;
        }

        $docStart = strrpos(substr($edited, 0, $constructor), '/**');
        $docEnd = $docStart === false ? false : strpos($edited, '*/', $docStart);

        if ($docStart === false || $docEnd === false || $docEnd > $constructor) {
            return $edited;
        }

        $doc = substr($edited, $docStart, $docEnd - $docStart);
        $eol = str_contains($edited, "\r\n") ? "\r\n" : "\n";
        preg_match_all('/^([ \t]*\*)[ \t]*@param[^\r\n]*/m', $doc, $params, PREG_OFFSET_CAPTURE);

        if ($params[0] === []) {
            return $edited;
        }

        $translations = array_values(array_filter($params[0], fn ($param) => str_contains($param[0], '$translations')));
        [$anchor, $offset] = $translations[0] ?? end($params[0]);
        $prefix = rtrim(substr($anchor, 0, strpos($anchor, '*') + 1));
        $line = "{$prefix} @param  {$type}|null  \${$field->name}  {$description}";
        $at = $docStart + $offset + ($translations ? 0 : strlen($anchor) + strlen($eol));

        return substr_replace($edited, $line.$eol, $at, 0);
    }

    /**
     * Add an item to a list unless the list holds its key already ('name' or $name); a list that
     * cannot be found is reported.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  string  $opening
     * @param  string  $item
     * @param  string  $place  The list, as reported when it is missing.
     * @param  string|null  $before
     * @return string
     */
    protected function add(string $source, string $path, string $opening, string $item, string $place, ?string $before = null): string
    {
        $content = $this->listContent($source, $opening);
        $key = preg_match("/^'[^']+'/", $item, $match) || preg_match('/\$\w+/', $item, $match) ? $match[0] : null;

        if ($content !== null && $key !== null && preg_match('/'.preg_quote($key, '/').'(?!\w)/', $content)) {
            return $source;
        }

        $edited = $this->addToList($source, $opening, $item, $before);

        if ($edited === null) {
            $this->misses[] = "{$path}: {$place}";

            return $source;
        }

        return $edited;
    }

    /**
     * Edit a file, recording whether it was updated, unchanged or missing.
     *
     * @param  string|false  $path
     * @param  Closure(string): string  $edit
     * @return void
     */
    protected function edit(string|false $path, Closure $edit): void
    {
        if ($path === false || ! $this->files->exists($path)) {
            if ($path !== false) {
                $this->report[$path] = '<fg=yellow>missing</>';
            }

            return;
        }

        $source = $this->files->get($path);
        $edited = $edit($source);

        if ($edited === $source) {
            $this->report[$path] = '<fg=gray>unchanged</>';

            return;
        }

        $this->files->put($path, $edited);
        $this->report[$path] = '<fg=green>updated</>';
    }

    /**
     * The path of a class of the slice under the root namespace's folder.
     *
     * @param  string  $class  The class relative to the root namespace, with slashes.
     * @return string
     */
    protected function srcPath(string $class): string
    {
        $artifact = $this->getArtifactOption();

        return $artifact
            ? $this->ugarit->basePath("artifacts/{$artifact}/src/{$class}.php")
            : $this->ugarit->path("{$class}.php");
    }

    /**
     * The existing model a foreign key names, in the model's folder.
     *
     * @param  string  $modelClass
     * @param  Field  $field
     * @return string|null
     */
    protected function relatedModel(string $modelClass, Field $field): ?string
    {
        if ($field->relation() === null) {
            return null;
        }

        $related = Str::beforeLast($modelClass, '\\').'\\'.Str::studly($field->relation());

        return class_exists($related) ? $related : null;
    }

    /**
     * The table of a model.
     *
     * @param  string  $model
     * @return string|null
     */
    protected function tableOf(string $model): ?string
    {
        try {
            return (new $model)->getTable();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Get the stub of the migration body (the migration is written by the migration creator).
     *
     * @return string
     */
    protected function getStub()
    {
        return dirname(__DIR__, 2).'/Database/Migrations/stubs/migration.body.stub';
    }
}
