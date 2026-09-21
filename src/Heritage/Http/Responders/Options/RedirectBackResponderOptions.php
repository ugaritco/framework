<?php

declare(strict_types=1);

namespace Heritage\Http\Responders\Options;

use Heritage\Responses\Outcome;

/**
 * Class RedirectBackResponderOptions
 *
 * Options DTO for RedirectBackResponder holding flash data and metadata.
 */
class RedirectBackResponderOptions extends ResponderOptions
{
    /**
     * Initialize RedirectBackResponder options.
     *
     * @param  Outcome  $outcome  The domain Outcome object returned by the operation.
     * @param  array<string, mixed>  $extra  Additional metadata.
     * @param  array<string, mixed>  $flash  Flash data for the redirect session.
     */
    public function __construct(
        Outcome $outcome,
        array $extra = [],
        public array $flash = [],
    ) {
        // Pass the outcome and extra data to parent constructor
        parent::__construct($outcome, $extra);
    }
}
