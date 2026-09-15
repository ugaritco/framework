<?php

use Heritage\Contracts\Validation\ValidationRule;

use function PHPStan\Testing\assertType;

$class = new class implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        assertType('Closure(string, string|null=): Heritage\Translation\PotentiallyTranslatedString', $fail);
    }
};
