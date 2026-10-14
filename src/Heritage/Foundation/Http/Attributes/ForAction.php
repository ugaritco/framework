<?php

namespace Heritage\Foundation\Http\Attributes;

use Attribute;

/**
 * The Capability or Feature a FormRequest feeds: its #[Authorize] ability is checked by the
 * request before validation, against the route model of the ability's class, and the
 * controller does not check it again. The request's own authorize() still runs after it.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ForAction
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string  $action  The Capability or Feature.
     */
    public function __construct(public string $action)
    {
        //
    }
}
