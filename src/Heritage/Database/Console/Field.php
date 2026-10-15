<?php

declare(strict_types=1);

namespace Heritage\Database\Console;

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
