<?php

declare(strict_types=1);

namespace Heritage\Http\Responders\Options;

use Heritage\Responses\Outcome;

/**
 * Options of a Blade view response.
 */
class ViewResponderOptions extends ResponderOptions
{
    /**
     * @param  Outcome  $outcome  The outcome to render.
     * @param  string  $view  The view name, e.g. "company::branch.edit".
     * @param  array<string, mixed>  $extra  Additional view variables.
     */
    public function __construct(
        Outcome $outcome,
        public readonly string $view,
        array $extra = [],
    ) {
        parent::__construct($outcome, $extra);
    }
}
