<?php

namespace Heritage\Console\View\Components\Mutators;

use Heritage\Support\Stringable;

class EnsurePunctuation
{
    /**
     * Ensures the given string ends with punctuation.
     *
     * @param  string  $string
     * @return string
     */
    public function __invoke($string)
    {
        if ((new Stringable($string))->doesntEndWith(['.', '?', '!', ':'])) {
            return "$string.";
        }

        return $string;
    }
}
