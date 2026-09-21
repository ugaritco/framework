<?php

declare(strict_types=1);

namespace Heritage\Http\Responders\Options;

use Heritage\Responses\Outcome;

/**
 * Class InertiaResponderOptions
 *
 * Options DTO for the InertiaResponder specifying component path, props, and flash data.
 */
class InertiaResponderOptions extends ResponderOptions
{
    /**
     * Initialize InertiaResponder options.
     *
     * @param  Outcome  $outcome  The domain Outcome object returned by the operation.
     * @param  string  $component  Inertia component name or path (defaults to 'Dashboard/Index').
     * @param  array<string, mixed>  $extra  Additional data to merge with component props.
     * @param  array<string, mixed>  $flash  Additional flash data for the session.
     */
    public function __construct(
        Outcome $outcome,
        public readonly string $component = 'Dashboard/Index',
        array $extra = [],
        public array $flash = [],
    ) {
        // Pass the outcome and extra data to parent constructor
        parent::__construct($outcome, $extra);
    }
}
