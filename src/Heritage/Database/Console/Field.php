<?php

declare(strict_types=1);

namespace Heritage\Database\Console;

use Heritage\Support\Str;
use InvalidArgumentException;

/**
 * A model field declared to the generators as "name:type[:modifier...]", e.g.
 * "branch_id:foreignId", "slug:string:unique" or "name:string:translatable".
 *
 * Modifiers: nullable, unique, index and translatable (stored in the translation table).
 */
final readonly class Field
{
    /**
     * The column types: their Blueprint method and the model cast they need, if any.
     *
     * @var array<string, array{0: string, 1: string|null}>
     */
    public const TYPES = [
        'string' => ['string', null],
        'text' => ['text', null],
        'integer' => ['integer', 'integer'],
        'bigInteger' => ['bigInteger', 'integer'],
        'boolean' => ['boolean', 'boolean'],
        'decimal' => ['decimal', 'decimal:2'],
        'float' => ['float', 'float'],
        'json' => ['json', 'array'],
        'date' => ['date', 'date'],
        'dateTime' => ['dateTime', 'datetime'],
        'foreignId' => ['foreignId', 'integer'],
    ];

    /**
     * The modifiers a field accepts.
     *
     * @var array<int, string>
     */
    public const MODIFIERS = ['nullable', 'unique', 'index', 'translatable'];

    /**
     * Create a field.
     *
     * @param  string  $name  The column name.
     * @param  string  $type  A key of TYPES.
     * @param  array<int, string>  $modifiers  Values of MODIFIERS.
     */
    public function __construct(
        public string $name,
        public string $type,
        public array $modifiers = [],
    ) {}

    /**
     * Parse a comma-separated list of field definitions.
     *
     * @param  string  $definition  e.g. "branch_id:foreignId,slug:string:unique,name:string:translatable".
     * @return array<int, static>
     *
     * @throws InvalidArgumentException When a name, type or modifier is invalid.
     */
    public static function parseMany(string $definition): array
    {
        $fields = [];

        foreach (array_filter(array_map('trim', explode(',', $definition))) as $field) {
            $field = static::parse($field);
            $fields[$field->name] = $field;
        }

        return array_values($fields);
    }

    /**
     * Parse one field definition.
     *
     * @param  string  $definition  e.g. "slug:string:unique".
     * @return static
     *
     * @throws InvalidArgumentException When the name, type or a modifier is invalid.
     */
    public static function parse(string $definition): static
    {
        $parts = array_map('trim', explode(':', $definition));
        $name = array_shift($parts);
        $type = array_shift($parts) ?: 'string';
        $type = array_values(array_filter(array_keys(static::TYPES), fn ($known) => strcasecmp($known, $type) === 0))[0] ?? $type;

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid field name [{$name}]; use snake_case.");
        }

        if (! isset(static::TYPES[$type])) {
            throw new InvalidArgumentException("Unknown type [{$type}] for field [{$name}]; use ".implode(', ', array_keys(static::TYPES)).'.');
        }

        foreach ($parts as $modifier) {
            if (! in_array($modifier, static::MODIFIERS, true)) {
                throw new InvalidArgumentException("Unknown modifier [{$modifier}] for field [{$name}]; use ".implode(', ', static::MODIFIERS).'.');
            }
        }

        if ($type === 'foreignId' && in_array('translatable', $parts, true)) {
            throw new InvalidArgumentException("The foreign key [{$name}] cannot be translatable.");
        }

        return new static($name, $type, array_values(array_unique($parts)));
    }

    /**
     * The field as a definition parse() reads back.
     *
     * @return string e.g. "slug:string:unique".
     */
    public function definition(): string
    {
        return implode(':', [$this->name, $this->type, ...$this->modifiers]);
    }

    /**
     * Whether the field has the given modifier.
     *
     * @param  string  $modifier
     * @return bool
     */
    public function has(string $modifier): bool
    {
        return in_array($modifier, $this->modifiers, true);
    }

    /**
     * The model cast of the field, if it needs one.
     *
     * @return string|null
     */
    public function cast(): ?string
    {
        return static::TYPES[$this->type][1];
    }

    /**
     * The relation a foreign key belongs to ("branch_id" belongs to "branch"), if any.
     *
     * @return string|null
     */
    public function relation(): ?string
    {
        return $this->type === 'foreignId' ? Str::camel(preg_replace('/_id$/', '', $this->name)) : null;
    }

    /**
     * The belongsTo() method of a foreign key, to the model of the same folder it names.
     *
     * @param  string  $owner  The model the method is added to.
     * @return string The method, indented as a class member, between empty lines.
     */
    public function belongsTo(string $owner): string
    {
        $relation = $this->relation();
        $related = Str::studly($relation);
        $owner = str_replace('_', ' ', Str::snake($owner));
        $label = str_replace('_', ' ', Str::snake($relation));

        return <<<PHP

            /**
             * The {$label} the {$owner} belongs to.
             *
             * @return BelongsTo<{$related}, \$this>
             */
            public function {$relation}(): BelongsTo
            {
                return \$this->belongsTo({$related}::class);
            }

        PHP;
    }

    /**
     * The name of the hasMany() inverse of a foreign key on its related model ("books" for a Book).
     *
     * @param  string  $owner  The model holding the foreign key.
     * @return string
     */
    public function inverse(string $owner): string
    {
        return Str::camel(Str::plural($owner));
    }

    /**
     * The hasMany() method of the related model of a foreign key, back to the model holding it.
     *
     * @param  string  $owner  The model holding the foreign key, in the related model's folder.
     * @return string The method, indented as a class member, between empty lines.
     */
    public function hasMany(string $owner): string
    {
        $method = $this->inverse($owner);
        $owners = str_replace('_', ' ', Str::snake($method));
        $related = str_replace('_', ' ', Str::snake($this->relation()));

        return <<<PHP

            /**
             * The {$owners} of the {$related}.
             *
             * @return HasMany<{$owner}, \$this>
             */
            public function {$method}(): HasMany
            {
                return \$this->hasMany({$owner}::class);
            }

        PHP;
    }

    /**
     * The PHP type of the field in a Parcel.
     *
     * @return string "int", "bool", "float", "array" or "string".
     */
    public function phpType(): string
    {
        return match ($this->type) {
            'integer', 'bigInteger', 'foreignId' => 'int',
            'boolean' => 'bool',
            'decimal', 'float' => 'float',
            'json' => 'array',
            default => 'string',
        };
    }

    /**
     * The validation rule of the field.
     *
     * @param  string|null  $foreignTable  The table a foreign key references, checked with exists.
     * @return string e.g. "required|integer|exists:branches,id".
     */
    public function rule(?string $foreignTable = null): string
    {
        $presence = $this->has('nullable') || $this->type === 'json' ? 'nullable' : 'required';

        return $presence.'|'.match ($this->type) {
            'foreignId' => 'integer'.($foreignTable ? "|exists:{$foreignTable},id" : ''),
            'integer', 'bigInteger' => 'integer',
            'boolean' => 'boolean',
            'decimal', 'float' => 'numeric',
            'json' => 'array',
            'date', 'dateTime' => 'date',
            'text' => 'string',
            default => 'string|max:255',
        };
    }

    /**
     * A fake value for an attribute, guessed from its name, then its cast.
     *
     * @param  string  $column
     * @param  string|null  $cast
     * @return string The PHP expression, for a factory's definition().
     */
    public static function fake(string $column, ?string $cast): string
    {
        $cast = $cast !== null ? Str::before(strtolower($cast), ':') : null;

        return match (true) {
            $column === 'slug' => '$this->faker->unique()->slug()',
            str_contains($column, 'email') => '$this->faker->unique()->safeEmail()',
            in_array($column, ['description', 'body', 'content', 'notes'], true) => '$this->faker->paragraph()',
            in_array($cast, ['bool', 'boolean'], true) => 'true',
            in_array($cast, ['int', 'integer'], true) => '$this->faker->numberBetween(1, 100)',
            in_array($cast, ['decimal', 'float', 'double', 'real'], true) => '$this->faker->randomFloat(2, 1, 1000)',
            in_array($cast, ['array', 'json', 'object', 'collection'], true) => '[]',
            in_array($cast, ['date', 'immutable_date'], true) => '$this->faker->date()',
            in_array($cast, ['datetime', 'immutable_datetime', 'timestamp'], true) => '$this->faker->dateTime()',
            default => '$this->faker->words(2, true)',
        };
    }

    /**
     * A fixed value for an attribute of the numbered sample record of a seeder, guessed from its
     * name, then its cast; unique columns differ from one record to the other.
     *
     * @param  string  $column
     * @param  string|null  $cast
     * @param  int  $number  The record's number, from 1.
     * @param  string  $model  The model's class basename, e.g. "Genre".
     * @return string The PHP expression, for a seeder's records().
     */
    public static function sample(string $column, ?string $cast, int $number, string $model): string
    {
        $cast = $cast !== null ? Str::before(strtolower($cast), ':') : null;
        $label = Str::ucfirst(str_replace('_', ' ', $column));

        return match (true) {
            $column === 'slug' => var_export(Str::kebab($model)."-{$number}", true),
            str_contains($column, 'email') => var_export(Str::kebab($model)."{$number}@example.com", true),
            in_array($cast, ['bool', 'boolean'], true) => 'true',
            in_array($cast, ['int', 'integer'], true) => (string) $number,
            in_array($cast, ['decimal', 'float', 'double', 'real'], true) => ($number * 10).'.0',
            in_array($cast, ['array', 'json', 'object', 'collection'], true) => '[]',
            in_array($cast, ['date', 'immutable_date'], true) => "'2026-01-0{$number}'",
            in_array($cast, ['datetime', 'immutable_datetime', 'timestamp'], true) => "'2026-01-0{$number} 09:00:00'",
            default => var_export("{$label} {$number}", true),
        };
    }

    /**
     * The comment line describing the column above its Blueprint statement, without indentation.
     *
     * @return string e.g. "// Published at (date, optional)" or "// Reference to the related author".
     */
    public function comment(): string
    {
        $label = str_replace('_', ' ', $this->name);

        if ($this->type === 'foreignId') {
            return '// Reference to the related '.str_replace('_', ' ', Str::snake($this->relation())).($this->has('nullable') ? ' (optional)' : '');
        }

        if ($this->name === 'slug' && $this->has('unique')) {
            return '// Unique URL-friendly slug identifier'.($this->has('translatable') ? ' (per locale)' : '');
        }

        $details = [match ($this->type) {
            'string' => 'text',
            'text' => 'long text',
            'integer', 'bigInteger' => 'whole number',
            'boolean' => 'true/false flag',
            'decimal' => 'decimal amount',
            'float' => 'number',
            'json' => 'structured data',
            'date' => 'date',
            'dateTime' => 'date and time',
        }];

        $unique = $this->has('translatable') ? 'unique per locale' : 'unique';

        foreach (['translatable' => 'translated', 'unique' => $unique, 'index' => 'indexed', 'nullable' => 'optional'] as $modifier => $detail) {
            if ($this->has($modifier)) {
                $details[] = $detail;
            }
        }

        return '// '.Str::ucfirst($label).' ('.implode(', ', $details).')';
    }

    /**
     * The Blueprint statement adding the column to an existing table, without indentation.
     *
     * The rows already there need a value: a field that is not nullable gets false (boolean) or
     * 0 (number) as its default, and is nullable otherwise. A translatable field is meant for
     * Schema::tableTranslatable(), which adds it to the translation table, unique per locale.
     *
     * @return string
     */
    public function addedColumn(): string
    {
        $column = '$table->'.static::TYPES[$this->type][0]."('{$this->name}'".($this->type === 'decimal' ? ', 10, 2' : '').')';

        $column .= match (true) {
            $this->has('nullable') => '->nullable()',
            $this->type === 'boolean' => '->default(false)',
            in_array($this->type, ['integer', 'bigInteger', 'decimal', 'float'], true) => '->default(0)',
            default => '->nullable()',
        };

        foreach (['unique', 'index', 'translatable'] as $modifier) {
            if ($this->has($modifier)) {
                $column .= "->{$modifier}()";
            }
        }

        if ($this->type === 'foreignId') {
            $column .= '->constrained()->nullOnDelete()';
        }

        return $column.';';
    }

    /**
     * The Blueprint statements dropping the column added by addedColumn(), without indentation.
     *
     * A translatable column is only dropped: Schema::tableTranslatable() drops its indexes.
     *
     * @return array<int, string>
     */
    public function droppedColumn(): array
    {
        if ($this->type === 'foreignId') {
            return ["\$table->dropConstrainedForeignId('{$this->name}');"];
        }

        $drops = [];

        if (! $this->has('translatable')) {
            foreach (['unique' => 'dropUnique', 'index' => 'dropIndex'] as $modifier => $drop) {
                if ($this->has($modifier)) {
                    $drops[] = "\$table->{$drop}(['{$this->name}']);";
                }
            }
        }

        return [...$drops, "\$table->dropColumn('{$this->name}');"];
    }

    /**
     * The Blueprint statement creating the column, without indentation.
     *
     * @return string e.g. "$table->foreignId('branch_id')->constrained();"
     */
    public function column(): string
    {
        $column = '$table->'.static::TYPES[$this->type][0]."('{$this->name}'".($this->type === 'decimal' ? ', 10, 2' : '').')';

        foreach (['nullable', 'unique', 'index', 'translatable'] as $modifier) {
            if ($this->has($modifier)) {
                $column .= "->{$modifier}()";
            }
        }

        if ($this->type === 'foreignId') {
            $column .= '->constrained()'.($this->has('nullable') ? '->nullOnDelete()' : '');
        }

        return $column.';';
    }
}
