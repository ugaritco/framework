<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Concerns;

use InvalidArgumentException;

/**
 * Reads typed input fields given as "code:string,note:?string,count:int": the parcel properties
 * and the request rules of an action's input. A field's type defaults to string; a leading "?"
 * makes it optional.
 */
trait ParsesInputFields
{
    /**
     * The validation rule of each input type.
     *
     * @var array<string, string>
     */
    protected static array $inputTypeRules = [
        'string' => 'string',
        'int' => 'integer',
        'bool' => 'boolean',
        'float' => 'numeric',
        'array' => 'array',
    ];

    /**
     * Parse a typed fields list.
     *
     * @param  string|null  $fields  "code:string,note:?string".
     * @return array<string, array{type: string, optional: bool}>
     *
     * @throws InvalidArgumentException When a field is unnamed or its type is unknown.
     */
    protected function parseInputFields(?string $fields): array
    {
        $parsed = [];

        foreach (array_filter(array_map('trim', explode(',', (string) $fields))) as $field) {
            [$name, $type] = array_map('trim', explode(':', $field, 2) + [1 => 'string']);
            $optional = str_starts_with($type, '?');
            $type = ltrim($type, '?') ?: 'string';

            if (! preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) {
                throw new InvalidArgumentException("The input field [{$field}] needs a name, as in code:string.");
            }

            if (! isset(static::$inputTypeRules[$type])) {
                throw new InvalidArgumentException("Unknown type [{$type}] of the input field [{$name}]; use ".implode(', ', array_keys(static::$inputTypeRules)).'.');
            }

            $parsed[$name] = ['type' => $type, 'optional' => $optional];
        }

        return $parsed;
    }

    /**
     * The rule line of an input field in a rules() array.
     *
     * @param  string  $name
     * @param  array{type: string, optional: bool}  $field
     * @return string
     */
    protected function inputFieldRule(string $name, array $field): string
    {
        $presence = $field['optional'] ? 'nullable' : 'required';

        return "            '{$name}' => '{$presence}|".static::$inputTypeRules[$field['type']]."',";
    }
}
