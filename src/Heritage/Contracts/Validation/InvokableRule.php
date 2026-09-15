<?php

namespace Heritage\Contracts\Validation;

use Closure;

/**
 * @deprecated see ValidationRule
 */
interface InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Heritage\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke(string $attribute, mixed $value, Closure $fail);
}
