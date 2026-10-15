<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui;

use Heritage\Filesystem\Filesystem;
use Heritage\Support\Str;
use ReflectionProperty;

/**
 * What the frontend of a model is generated from, whatever the stack: its fields read from its
 * store request rules, the artifact's migrations and its casts, each with its kind, whether it is
 * required, nullable or translatable and the model it refers to; the field naming a record and the
 * one confirming its deletion.
 */
class Blueprint
{
    /**
     * The field kinds of the migration column types.
     *
     * @var array<string, string>
     */
    protected const COLUMN_KINDS = [
        'string' => 'string', 'char' => 'string', 'uuid' => 'string', 'ulid' => 'string',
        'text' => 'text', 'tinyText' => 'text', 'mediumText' => 'text', 'longText' => 'text',
        'integer' => 'integer', 'tinyInteger' => 'integer', 'smallInteger' => 'integer', 'mediumInteger' => 'integer',
        'bigInteger' => 'integer', 'unsignedInteger' => 'integer', 'unsignedTinyInteger' => 'integer',
        'unsignedSmallInteger' => 'integer', 'unsignedMediumInteger' => 'integer', 'unsignedBigInteger' => 'integer',
        'foreignId' => 'integer',
        'decimal' => 'decimal', 'float' => 'decimal', 'double' => 'decimal', 'unsignedDecimal' => 'decimal',
        'boolean' => 'boolean',
        'date' => 'date',
        'dateTime' => 'datetime', 'dateTimeTz' => 'datetime', 'timestamp' => 'datetime', 'timestampTz' => 'datetime',
        'json' => 'json', 'jsonb' => 'json',
    ];

    /**
     * Create a blueprint.
     *
     * @param  string  $artifact
     * @param  string  $model
     * @param  array<string, array{name: string, kind: string, rule: string, required: bool, nullable: bool, translatable: bool, related: string|null}>  $fields
     * @param  array<string, string>  $relatedLabels  The label field of each related model.
     */
    public function __construct(
        public readonly string $artifact,
        public readonly string $model,
        public readonly array $fields,
        public readonly array $relatedLabels = [],
    ) {
    }

    /**
     * Read the blueprint of an artifact's model.
     *
     * @param  Filesystem  $files
     * @param  string  $base  The application's base path.
     * @param  string  $artifact
     * @param  string  $model
     * @param  bool  $withRelated  Whether to read the label field of the related models.
     * @return static
     */
    public static function read(Filesystem $files, string $base, string $artifact, string $model, bool $withRelated = true): static
    {
        $class = static::modelClass($artifact, $model);
        $instance = new $class;
        $translatable = static::declaredTranslatable($instance);
        $rules = static::rules($files, $base, $artifact, $model);

        if ($rules === []) {
            $rules = array_fill_keys([...$instance->getFillable(), ...$translatable], '');
        }

        $columns = static::migrationColumns($files, $base, $artifact, $instance->getTable());
        $casts = $instance->getCasts();
        $fields = [];

        foreach ($rules as $name => $rule) {
            $column = $columns[$name] ?? null;
            $kind = $column['kind'] ?? static::castKind($casts[$name] ?? null) ?? static::ruleKind($rule);
            $related = null;

            if (str_ends_with($name, '_id') && class_exists(static::modelClass($artifact, Str::studly(Str::beforeLast($name, '_id'))))) {
                $kind = 'foreign';
                $related = Str::studly(Str::beforeLast($name, '_id'));
            }

            $fields[$name] = [
                'name' => $name,
                'kind' => $kind,
                'rule' => $rule,
                'required' => str_starts_with($rule, 'required'),
                'nullable' => str_contains($rule, 'nullable') || ($column['nullable'] ?? false),
                'translatable' => in_array($name, $translatable, true) || ($column['translatable'] ?? false),
                'related' => $related,
            ];
        }

        $relatedLabels = [];

        foreach ($withRelated ? array_unique(array_filter(array_column($fields, 'related'))) : [] as $related) {
            $relatedLabels[$related] = static::read($files, $base, $artifact, $related, false)->labelField();
        }

        return new static($artifact, $model, $fields, $relatedLabels);
    }

    /**
     * The class of an artifact's model.
     *
     * @param  string  $artifact
     * @param  string  $model
     * @return string
     */
    public static function modelClass(string $artifact, string $model): string
    {
        return 'Ugarit\\Artifacts\\'.Str::studly($artifact).'\\Models\\'.$model;
    }

    /**
     * The fields shown in the forms, the summary and the table: all but json fields.
     *
     * @param  array<string, array<string, mixed>>|null  $fields  The fields to filter, all by default.
     * @return array<string, array<string, mixed>>
     */
    public function editable(?array $fields = null): array
    {
        return array_filter($fields ?? $this->fields, fn (array $field) => $field['kind'] !== 'json');
    }

    /**
     * The translatable fields shown in the forms.
     *
     * @return array<string, array<string, mixed>>
     */
    public function translatable(): array
    {
        return array_filter($this->editable(), fn (array $field) => $field['translatable']);
    }

    /**
     * Whether the model has translatable fields to edit per locale.
     *
     * @return bool
     */
    public function isTranslatable(): bool
    {
        return $this->translatable() !== [];
    }

    /**
     * The fields but the translatable ones.
     *
     * @return array<string, array<string, mixed>>
     */
    public function plain(): array
    {
        return array_diff_key($this->fields, $this->translatable());
    }

    /**
     * The foreign key fields, whose model exists.
     *
     * @param  array<string, array<string, mixed>>|null  $fields  The fields to filter, all by default.
     * @return array<string, array<string, mixed>>
     */
    public function foreign(?array $fields = null): array
    {
        return array_filter($fields ?? $this->fields, fn (array $field) => $field['kind'] === 'foreign');
    }

    /**
     * The distinct models the foreign keys refer to.
     *
     * @param  array<string, array<string, mixed>>|null  $fields  The fields to look in, all by default.
     * @return array<int, string>
     */
    public function relatedModels(?array $fields = null): array
    {
        return array_values(array_unique(array_column($this->foreign($fields), 'related')));
    }

    /**
     * The field naming a record: name, title or slug, else its first string field, else id.
     *
     * @param  array<string, array<string, mixed>>|null  $fields  The fields to choose from, all by default.
     * @return string
     */
    public function labelField(?array $fields = null): string
    {
        $fields ??= $this->fields;

        foreach (['name', 'title', 'slug'] as $name) {
            if (isset($fields[$name])) {
                return $name;
            }
        }

        foreach ($fields as $name => $field) {
            if ($field['kind'] === 'string') {
                return $name;
            }
        }

        return 'id';
    }

    /**
     * The field typed to confirm a deletion: the slug, else the label field when it is required
     * and not translated, else the id.
     *
     * @return string
     */
    public function confirmField(): string
    {
        if (isset($this->fields['slug'])) {
            return 'slug';
        }

        $label = $this->labelField();

        return $label !== 'id' && ! $this->fields[$label]['nullable'] && ! $this->fields[$label]['translatable'] ? $label : 'id';
    }

    /**
     * The translation key group of the model ("artifact::model").
     *
     * @return string
     */
    public function lang(): string
    {
        return $this->artifact.'::'.Str::snake($this->model);
    }

    /**
     * The translatable attributes a model declares.
     *
     * @param  object  $instance
     * @return array<int, string>
     */
    protected static function declaredTranslatable(object $instance): array
    {
        if (! property_exists($instance, 'translatable')) {
            return [];
        }

        return (array) (new ReflectionProperty($instance, 'translatable'))->getValue($instance);
    }

    /**
     * The rules of the model's store request, as field => rule string.
     *
     * @param  Filesystem  $files
     * @param  string  $base
     * @param  string  $artifact
     * @param  string  $model
     * @return array<string, string>
     */
    protected static function rules(Filesystem $files, string $base, string $artifact, string $model): array
    {
        $path = "{$base}/artifacts/{$artifact}/src/Http/Requests/{$model}/Capabilities/Store{$model}Request.php";

        if (! $files->exists($path)) {
            return [];
        }

        $source = Str::after($files->get($path), 'function rules(');
        preg_match_all("/'(\\w+)'\\s*=>\\s*'([^']*)'/", $source, $matches, PREG_SET_ORDER);
        $rules = [];

        foreach ($matches as [, $name, $rule]) {
            if ($name !== 'force') {
                $rules[$name] = $rule;
            }
        }

        return $rules;
    }

    /**
     * The columns the artifact's migrations add to a table, with their kind and modifiers.
     *
     * @param  Filesystem  $files
     * @param  string  $base
     * @param  string  $artifact
     * @param  string  $table
     * @return array<string, array{kind: string, nullable: bool, translatable: bool}>
     */
    protected static function migrationColumns(Filesystem $files, string $base, string $artifact, string $table): array
    {
        $directory = "{$base}/artifacts/{$artifact}/database/migrations";
        $columns = [];

        if (! $files->isDirectory($directory)) {
            return $columns;
        }

        foreach ($files->files($directory) as $file) {
            foreach (preg_split('/Schema::/', $files->get($file->getPathname())) as $block) {
                if (! preg_match("/^(?:create|createTranslatable|table)\\('(\\w+)'/", $block, $name) || ! in_array($name[1], [$table, Str::singular($table).'_translations'], true)) {
                    continue;
                }

                preg_match_all("/\\\$table->(\\w+)\\('(\\w+)'[^;]*;/", $block, $matches, PREG_SET_ORDER);

                foreach ($matches as [$statement, $method, $column]) {
                    if (isset(self::COLUMN_KINDS[$method])) {
                        $columns[$column] = [
                            'kind' => self::COLUMN_KINDS[$method],
                            'nullable' => str_contains($statement, '->nullable()'),
                            'translatable' => str_contains($statement, '->translatable()'),
                        ];
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * The field kind of a model cast, if it gives one.
     *
     * @param  string|null  $cast
     * @return string|null
     */
    protected static function castKind(?string $cast): ?string
    {
        return match (true) {
            $cast === null => null,
            in_array($cast, ['bool', 'boolean'], true) => 'boolean',
            in_array($cast, ['int', 'integer'], true) => 'integer',
            in_array($cast, ['float', 'double', 'real'], true), str_starts_with($cast, 'decimal') => 'decimal',
            $cast === 'date', str_starts_with($cast, 'date:'), $cast === 'immutable_date' => 'date',
            str_contains($cast, 'datetime'), $cast === 'timestamp' => 'datetime',
            in_array($cast, ['array', 'json', 'object', 'collection'], true) => 'json',
            default => null,
        };
    }

    /**
     * The field kind a validation rule implies; a string when it implies none.
     *
     * @param  string  $rule
     * @return string
     */
    protected static function ruleKind(string $rule): string
    {
        $rules = explode('|', $rule);

        return match (true) {
            in_array('boolean', $rules, true) => 'boolean',
            in_array('integer', $rules, true) => 'integer',
            in_array('numeric', $rules, true) => 'decimal',
            in_array('date', $rules, true) => 'date',
            in_array('array', $rules, true) => 'json',
            default => 'string',
        };
    }
}
