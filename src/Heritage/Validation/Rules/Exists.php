<?php

namespace Heritage\Validation\Rules;

use Heritage\Support\Traits\Conditionable;
use Stringable;

class Exists implements Stringable
{
    use Conditionable, DatabaseRule;

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('exists:%s,%s,%s',
            $this->table,
            $this->column,
            $this->formatWheres()
        ), ',');
    }
}
