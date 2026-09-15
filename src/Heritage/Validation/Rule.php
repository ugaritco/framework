<?php

namespace Heritage\Validation;

use Heritage\Support\Arr;
use Heritage\Support\Traits\Macroable;
use Heritage\Validation\Rules\AnyOf;
use Heritage\Validation\Rules\ArrayKeys;
use Heritage\Validation\Rules\ArrayRule;
use Heritage\Validation\Rules\Can;
use Heritage\Validation\Rules\Date;
use Heritage\Validation\Rules\Dimensions;
use Heritage\Validation\Rules\Email;
use Heritage\Validation\Rules\Enum;
use Heritage\Validation\Rules\ExcludeIf;
use Heritage\Validation\Rules\ExcludeUnless;
use Heritage\Validation\Rules\Exists;
use Heritage\Validation\Rules\File;
use Heritage\Validation\Rules\ImageFile;
use Heritage\Validation\Rules\In;
use Heritage\Validation\Rules\NotIn;
use Heritage\Validation\Rules\Numeric;
use Heritage\Validation\Rules\ProhibitedIf;
use Heritage\Validation\Rules\ProhibitedUnless;
use Heritage\Validation\Rules\RequiredIf;
use Heritage\Validation\Rules\RequiredUnless;
use Heritage\Validation\Rules\StringRule;
use Heritage\Validation\Rules\Unique;

class Rule
{
    use Macroable;

    /**
     * Get a can constraint builder instance.
     *
     * @param  string  $ability
     * @param  mixed  ...$arguments
     * @return \Heritage\Validation\Rules\Can
     */
    public static function can($ability, ...$arguments)
    {
        return new Can($ability, $arguments);
    }

    /**
     * Apply the given rules if the given condition is truthy.
     *
     * @param  callable|bool  $condition
     * @param  \Heritage\Contracts\Validation\ValidationRule|\Heritage\Contracts\Validation\InvokableRule|\Heritage\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \Heritage\Contracts\Validation\ValidationRule|\Heritage\Contracts\Validation\InvokableRule|\Heritage\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \Heritage\Validation\ConditionalRules
     */
    public static function when($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $rules, $defaultRules);
    }

    /**
     * Apply the given rules if the given condition is falsy.
     *
     * @param  callable|bool  $condition
     * @param  \Heritage\Contracts\Validation\ValidationRule|\Heritage\Contracts\Validation\InvokableRule|\Heritage\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \Heritage\Contracts\Validation\ValidationRule|\Heritage\Contracts\Validation\InvokableRule|\Heritage\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \Heritage\Validation\ConditionalRules
     */
    public static function unless($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $defaultRules, $rules);
    }

    /**
     * Get an array rule builder instance.
     *
     * @param  array|null  $keys
     * @return \Heritage\Validation\Rules\ArrayRule
     */
    public static function array($keys = null)
    {
        return new ArrayRule(...func_get_args());
    }

    /**
     * Get an array keys rule builder instance.
     *
     * @param  \Heritage\Contracts\Support\Arrayable|array|string  $keys
     * @return \Heritage\Validation\Rules\ArrayKeys
     */
    public static function arrayKeys($keys)
    {
        return new ArrayKeys(...func_get_args());
    }

    /**
     * Create a new nested rule set.
     *
     * @param  callable  $callback
     * @return \Heritage\Validation\NestedRules
     */
    public static function forEach($callback)
    {
        return new NestedRules($callback);
    }

    /**
     * Get a unique constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Heritage\Validation\Rules\Unique
     */
    public static function unique($table, $column = 'NULL')
    {
        return new Unique($table, $column);
    }

    /**
     * Get an exists constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Heritage\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Exists($table, $column);
    }

    /**
     * Get an in rule builder instance.
     *
     * @param  \Heritage\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Heritage\Validation\Rules\In
     */
    public static function in($values)
    {
        return new In(...func_get_args());
    }

    /**
     * Get a not_in rule builder instance.
     *
     * @param  \Heritage\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Heritage\Validation\Rules\NotIn
     */
    public static function notIn($values)
    {
        return new NotIn(...func_get_args());
    }

    /**
     * Get a required_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Heritage\Validation\Rules\RequiredIf
     */
    public static function requiredIf($callback)
    {
        return new RequiredIf($callback);
    }

    /**
     * Get a required_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool|null  $callback
     * @return \Heritage\Validation\Rules\RequiredUnless
     */
    public static function requiredUnless($callback)
    {
        return new RequiredUnless($callback);
    }

    /**
     * Get an exclude_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Heritage\Validation\Rules\ExcludeIf
     */
    public static function excludeIf($callback)
    {
        return new ExcludeIf($callback);
    }

    /**
     * Get an exclude_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Heritage\Validation\Rules\ExcludeUnless
     */
    public static function excludeUnless($callback)
    {
        return new ExcludeUnless($callback);
    }

    /**
     * Get a prohibited_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Heritage\Validation\Rules\ProhibitedIf
     */
    public static function prohibitedIf($callback)
    {
        return new ProhibitedIf($callback);
    }

    /**
     * Get a prohibited_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Heritage\Validation\Rules\ProhibitedUnless
     */
    public static function prohibitedUnless($callback)
    {
        return new ProhibitedUnless($callback);
    }

    /**
     * Get a date rule builder instance.
     *
     * @return \Heritage\Validation\Rules\Date
     */
    public static function date()
    {
        return new Date;
    }

    /**
     * Get a datetime rule builder instance.
     */
    public static function dateTime(): Date
    {
        return (new Date)->format('Y-m-d H:i:s');
    }

    /**
     * Get an email rule builder instance.
     *
     * @return \Heritage\Validation\Rules\Email
     */
    public static function email()
    {
        return new Email;
    }

    /**
     * Get an enum rule builder instance.
     *
     * @param  class-string  $type
     * @return \Heritage\Validation\Rules\Enum
     */
    public static function enum($type)
    {
        return new Enum($type);
    }

    /**
     * Get a file rule builder instance.
     *
     * @return \Heritage\Validation\Rules\File
     */
    public static function file()
    {
        return new File;
    }

    /**
     * Get an image file rule builder instance.
     *
     * @param  bool  $allowSvg
     * @return \Heritage\Validation\Rules\ImageFile
     */
    public static function imageFile($allowSvg = false)
    {
        return new ImageFile($allowSvg);
    }

    /**
     * Get a dimensions rule builder instance.
     *
     * @param  array  $constraints
     * @return \Heritage\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Dimensions($constraints);
    }

    /**
     * Get a string rule builder instance.
     *
     * @return \Heritage\Validation\Rules\StringRule
     */
    public static function string()
    {
        return new StringRule;
    }

    /**
     * Get a numeric rule builder instance.
     *
     * @return \Heritage\Validation\Rules\Numeric
     */
    public static function numeric()
    {
        return new Numeric;
    }

    /**
     * Get an "any of" rule builder instance.
     *
     * @param  array  $rules
     * @return \Heritage\Validation\Rules\AnyOf
     *
     * @throws \InvalidArgumentException
     */
    public static function anyOf($rules)
    {
        return new AnyOf($rules);
    }

    /**
     * Get a contains rule builder instance.
     *
     * @param  \Heritage\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Heritage\Validation\Rules\Contains
     */
    public static function contains($values)
    {
        return new Rules\Contains(...func_get_args());
    }

    /**
     * Get a "does not contain" rule builder instance.
     *
     * @param  \Heritage\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Heritage\Validation\Rules\DoesntContain
     */
    public static function doesntContain($values)
    {
        return new Rules\DoesntContain(...func_get_args());
    }

    /**
     * Compile a set of rules for an attribute.
     *
     * @param  string  $attribute
     * @param  array  $rules
     * @param  array|null  $data
     * @return object|\stdClass
     */
    public static function compile($attribute, $rules, $data = null)
    {
        $parser = new ValidationRuleParser(
            Arr::undot(Arr::wrap($data))
        );

        if (is_array($rules) && ! array_is_list($rules)) {
            $nested = [];

            foreach ($rules as $key => $rule) {
                $nested[$attribute.'.'.$key] = $rule;
            }

            $rules = $nested;
        } else {
            $rules = [$attribute => $rules];
        }

        return $parser->explode(ValidationRuleParser::filterConditionalRules($rules, $data));
    }
}
