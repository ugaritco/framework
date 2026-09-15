<?php

namespace Heritage\Contracts\Validation;

use Heritage\Validation\Validator;

interface ValidatorAwareRule
{
    /**
     * Set the current validator.
     *
     * @param  \Heritage\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator);
}
