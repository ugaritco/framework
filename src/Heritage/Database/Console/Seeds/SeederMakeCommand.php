<?php

namespace Heritage\Database\Console\Seeds;

use Heritage\Console\GeneratorCommand;
use Heritage\Database\Console\Field;
use Heritage\Support\Str;
use Heritage\Translation\Locales;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'make:seeder')]
class SeederMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:seeder
                    {name : The name of the seeder}
                    {--m|model= : The name of the model}
                    {--artifact= : The target modular artifact name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seeder class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->promptForArtifact('Which modular artifact should this seeder belong to?');

        if (! $this->option('model') && $this->input->isInteractive()) {
            $this->promptForModel('Which model should this seeder correspond to? (optional)');
        }

        parent::handle();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('model')) {
            return $this->resolveStubPath('/stubs/seeder.model.stub');
        }

        return $this->resolveStubPath('/stubs/seeder.stub');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        if ($this->option('model')) {
            $namespaceModel = $this->qualifyModel($this->option('model'));
            $model = class_basename($namespaceModel);

            $replace = [
                'NamespacedDummyModel' => $namespaceModel,
                '{{ namespacedModel }}' => $namespaceModel,
                '{{namespacedModel}}' => $namespaceModel,
                'DummyModel' => $model,
                '{{ model }}' => $model,
                '{{model}}' => $model,
            ];

            $stub = str_replace(array_keys($replace), array_values($replace), $stub);
            $records = $this->records($namespaceModel);

            return $records === [] ? $stub : preg_replace('/^            \/\/(\r?\n)/m', implode("\n", $records).'$1', $stub, 1);
        }

        return $stub;
    }

    /**
     * Two fixed records of an existing model: a sample value for each column of its table, and
     * of its translation table given for every available locale; a foreign key is left to the factory.
     *
     * @param  string  $model
     * @return array<int, string> The lines of the records, indented in records().
     */
    protected function records(string $model): array
    {
        if (! class_exists($model)) {
            return [];
        }

        try {
            $instance = new $model;
            $locales = Locales::resolver()->available();
            [$columns, $translated] = $this->recordColumns($instance);
        } catch (Throwable) {
            return [];
        }

        if ($columns === [] && $translated === []) {
            return [];
        }

        $lines = [];

        foreach ([1, 2] as $number) {
            $lines[] = '            [';

            foreach ($columns as $column => $cast) {
                $lines[] = "                '{$column}' => ".Field::sample($column, $cast, $number, class_basename($model)).',';
            }

            foreach ($translated as $column => $cast) {
                $value = Field::sample($column, $cast, $number, class_basename($model));
                $lines[] = "                '{$column}' => [".implode(', ', array_map(fn ($locale) => var_export((string) $locale, true)." => {$value}", $locales)).'],';
            }

            $lines[] = '            ],';
        }

        return $lines;
    }

    /**
     * The columns a record gives, each with the cast its sample value follows: those of the model's
     * table, and of its translation table, but the keys, foreign keys, locale and timestamps.
     * Before the tables are migrated, the fillable and translatable attributes are used instead.
     *
     * @param  \Heritage\Database\Eloquent\Model  $instance
     * @return array{0: array<string, string|null>, 1: array<string, string|null>} The columns of the table, then of the translation table.
     */
    protected function recordColumns(object $instance): array
    {
        $casts = $instance->getCasts();
        $translatable = method_exists($instance, 'getTranslatable') ? $instance->getTranslatable() : [];
        $schema = $instance->getConnection()->getSchemaBuilder();

        if (! $schema->hasTable($instance->getTable())) {
            $plain = array_diff($instance->getFillable(), $translatable);

            return array_map(
                fn (array $columns) => array_combine($columns, array_map(fn ($column) => $casts[$column] ?? null, $columns)),
                [array_values(array_filter($plain, fn ($column) => ! str_ends_with($column, '_id'))), array_values($translatable)]
            );
        }

        $skip = [
            $instance->getKeyName(),
            $instance->getCreatedAtColumn(),
            $instance->getUpdatedAtColumn(),
            method_exists($instance, 'getDeletedAtColumn') ? $instance->getDeletedAtColumn() : 'deleted_at',
        ];

        $read = function (string $table, array $skip) use ($schema, $casts): array {
            $columns = [];

            foreach ($schema->getColumns($table) as $column) {
                if (! $column['auto_increment'] && ! in_array($column['name'], $skip, true) && ! str_ends_with($column['name'], '_id')) {
                    $columns[$column['name']] = $casts[$column['name']] ?? $this->columnCast($column);
                }
            }

            return $columns;
        };

        $columns = $read($instance->getTable(), $skip);
        $translated = [];

        if ($translatable !== [] && $schema->hasTable($table = $instance->getTranslationTable())) {
            $translated = array_diff_key($read($table, [
                ...$skip, 'id', $instance->getTranslationForeignKey(), $instance->getTranslationLocaleColumn(),
            ]), $columns);
        }

        return [$columns, $translated];
    }

    /**
     * The cast a column's sample value follows, from its database type.
     *
     * @param  array{type_name: string, type: string}  $column
     * @return string|null "boolean", "integer", "float", "date", "datetime", "array", or null for a string.
     */
    protected function columnCast(array $column): ?string
    {
        $type = strtolower($column['type_name']);

        return match (true) {
            in_array($type, ['bool', 'boolean', 'bit'], true) || strtolower($column['type']) === 'tinyint(1)' => 'boolean',
            in_array($type, ['int', 'integer', 'bigint', 'smallint', 'mediumint', 'tinyint', 'int2', 'int4', 'int8'], true) => 'integer',
            in_array($type, ['decimal', 'numeric', 'float', 'double', 'real', 'float4', 'float8', 'money'], true) => 'float',
            $type === 'date' => 'date',
            in_array($type, ['datetime', 'datetime2', 'timestamp', 'timestamptz', 'smalldatetime'], true) => 'datetime',
            in_array($type, ['json', 'jsonb'], true) => 'array',
            default => null,
        };
    }

    /**
     * Qualify the given model class name.
     *
     * @param  string  $model
     * @return string
     */
    protected function qualifyModel(string $model)
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        if ($artifact = $this->getArtifactOption()) {
            $studly = Str::studly($artifact);

            if (Str::startsWith($model, "Ugarit\\Artifacts\\{$studly}\\Models\\")) {
                return $model;
            }

            return "Ugarit\\Artifacts\\{$studly}\\Models\\{$model}";
        }

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $this->ugarit->getNamespace().'Models\\'.$model
            : $this->ugarit->getNamespace().$model;
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return is_file($customPath = $this->ugarit->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name));

        if ($artifact = $this->getArtifactOption()) {
            return $this->ugarit->basePath("artifacts/{$artifact}/database/seeders/").$name.'.php';
        }

        if (is_dir($this->ugarit->databasePath().'/seeds')) {
            return $this->ugarit->databasePath().'/seeds/'.$name.'.php';
        }

        return $this->ugarit->databasePath().'/seeders/'.$name.'.php';
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        if ($artifact = $this->getArtifactOption()) {
            $studly = Str::studly($artifact);

            return "Ugarit\\Artifacts\\{$studly}\\Database\\Seeders\\";
        }

        return 'Database\Seeders\\';
    }
}
