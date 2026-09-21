<?php

declare(strict_types=1);

namespace Heritage\Http\Responders\Options;

use Heritage\Responses\Outcome;

/**
 * Class ResponderOptions
 *
 * Base Data Transfer Object for passing options and parameters to HTTP Responders.
 *
 * Guarantees:
 * 1. A mandatory domain Outcome/Response object originating from a UseCase, Service, or Feature.
 * 2. An optional extra metadata array for fine-tuning the presentation layer output.
 */
class ResponderOptions
{
    /**
     * The domain Outcome object returned by the UseCase, Service, or Feature.
     *
     * @var Outcome
     */
    public readonly Outcome $outcome;

    /**
     * Backward-compatible alias referencing the same Outcome instance.
     *
     * @var Outcome
     */
    public readonly Outcome $response;

    /**
     * Initialize the base responder options with the required Outcome object.
     *
     * @param  Outcome  $outcome  The domain Outcome (or Response) object.
     * @param  array<string, mixed>  $extra  Optional extra metadata.
     */
    public function __construct(
        Outcome $outcome,
        public readonly array $extra = []
    ) {
        // Set both outcome and response to the same instance for seamless compatibility
        $this->outcome = $outcome;
        $this->response = $outcome;
    }
}
