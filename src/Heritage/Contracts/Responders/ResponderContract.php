<?php

declare(strict_types=1);

namespace Heritage\Contracts\Responders;

use Heritage\Http\Responders\Options\ResponderOptions;
use Heritage\Responses\Outcome;

/**
 * Interface ResponderContract
 *
 * Defines the contract for transforming a domain execution Outcome
 * into a consumer-facing Response (HTTP, CLI, JSON, Inertia, etc.).
 *
 * In the Ugarit architectural philosophy:
 * - Capability executes the atomic operation.
 * - Feature orchestrates the workflow.
 * - Outcome represents the pure domain execution result.
 * - Responder translates the Outcome into the consumer-facing Response.
 */
interface ResponderContract
{
    /**
     * Transform a domain Outcome or options envelope into a consumer Response.
     *
     * @param ResponderOptions|Outcome|null $options Strongly-typed options, domain Outcome, or null.
     * @return mixed Final consumer-facing response.
     */
    public function respond(ResponderOptions|Outcome|null $options = null): mixed;
}
