<?php

namespace Heritage\Contracts\JsonSchema;

use Closure;

interface JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchema): array<string, \Heritage\JsonSchema\Types\Type>)|array<string, \Heritage\JsonSchema\Types\Type>  $properties
     * @return \Heritage\JsonSchema\Types\ObjectType
     */
    public function object(Closure|array $properties = []);

    /**
     * Create a new array property instance.
     *
     * @return \Heritage\JsonSchema\Types\ArrayType
     */
    public function array();

    /**
     * Create a new string property instance.
     *
     * @return \Heritage\JsonSchema\Types\StringType
     */
    public function string();

    /**
     * Create a new integer property instance.
     *
     * @return \Heritage\JsonSchema\Types\IntegerType
     */
    public function integer();

    /**
     * Create a new number property instance.
     *
     * @return \Heritage\JsonSchema\Types\NumberType
     */
    public function number();

    /**
     * Create a new boolean property instance.
     *
     * @return \Heritage\JsonSchema\Types\BooleanType
     */
    public function boolean();

    /**
     * Create a new multi-type union instance.
     *
     * @param  array<int, string>  $types
     * @return \Heritage\JsonSchema\Types\UnionType
     */
    public function union(array $types);

    /**
     * Create a new anyOf schema instance.
     *
     * @param  (Closure(JsonSchema): array<int, \Heritage\JsonSchema\Types\Type>)|array<int, \Heritage\JsonSchema\Types\Type>  $schemas
     * @return \Heritage\JsonSchema\Types\AnyOfType
     */
    public function anyOf(Closure|array $schemas);
}
