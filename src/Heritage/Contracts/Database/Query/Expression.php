<?php

namespace Heritage\Contracts\Database\Query;

use Heritage\Database\Grammar;

interface Expression
{
    /**
     * Get the value of the expression.
     *
     * @param  \Heritage\Database\Grammar  $grammar
     * @return string|int|float
     */
    public function getValue(Grammar $grammar);
}
