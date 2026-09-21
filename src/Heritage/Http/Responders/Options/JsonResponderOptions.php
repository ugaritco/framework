<?php

declare(strict_types=1);

namespace Heritage\Http\Responders\Options;

use Heritage\Responses\Outcome;

/**
 * Class JsonResponderOptions
 *
 * Options DTO for JsonResponder with optional HTTP status code override and extra metadata.
 */
class JsonResponderOptions extends ResponderOptions
{
    /**
     * Initialize JsonResponder options.
     *
     * @param  Outcome  $outcome  The domain Outcome object returned by the operation.
     * @param  int|null  $status  Optional HTTP status code override.
     * @param  array<string, mixed>  $extra  Additional metadata to merge into the JSON envelope.
     */
    public function __construct(
        Outcome $outcome,
        public readonly ?int $status = null,
        array $extra = [],
    ) {
        // Pass the outcome and extra data to parent constructor
        parent::__construct($outcome, $extra);
    }
}
