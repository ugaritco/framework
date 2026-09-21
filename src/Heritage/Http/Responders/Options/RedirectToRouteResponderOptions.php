<?php

declare(strict_types=1);

namespace Heritage\Http\Responders\Options;

use Heritage\Responses\Outcome;

/**
 * Class RedirectToRouteResponderOptions
 *
 * Options DTO for RedirectToRouteResponder holding route name, parameters, and flash data.
 */
class RedirectToRouteResponderOptions extends ResponderOptions
{
    /**
     * Initialize RedirectToRouteResponder options.
     *
     * @param  Outcome  $outcome  The domain Outcome object returned by the operation.
     * @param  string  $routeName  Target route name.
     * @param  array<string, mixed>  $parameters  Route parameters.
     * @param  array<string, mixed>  $extra  Additional metadata.
     * @param  array<string, mixed>  $flash  Flash data for the redirect session.
     */
    public function __construct(
        Outcome $outcome,
        public readonly string $routeName,
        public readonly array $parameters = [],
        array $extra = [],
        public array $flash = [],
    ) {
        // Pass the outcome and extra data to parent constructor
        parent::__construct($outcome, $extra);
    }
}
