<?php

namespace Heritage\Database\Query;

use Heritage\Contracts\Database\Query\Expression as ExpressionContract;
use Heritage\Database\Grammar;

/**
 * @template TValue of literal-string|int|float
 */
class Expression implements ExpressionContract
{
    /**
     * Create a new raw query expression.
     *
     * @param  TValue  $value
     */
    public function __construct(
        protected $value,
    ) {
    }

    /**
     * Get the value of the expression.
     *
     * @param  \Heritage\Database\Grammar  $grammar
     * @return TValue
     */
    public function getValue(Grammar $grammar)
    {
        return $this->value;
    }
}
