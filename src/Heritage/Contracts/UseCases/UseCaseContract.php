<?php

declare(strict_types=1);

namespace Heritage\Contracts\UseCases;

use Heritage\DTOs\DTO;
use Heritage\Responses\Outcome;

/**
 * Interface UseCaseContract
 *
 * Core architectural contract for all atomic domain use cases in the Ugarit Ecosystem.
 *
 * Architectural Rules:
 * - A UseCase is the smallest atomic unit of work in the system, responsible for persisting or
 *   modifying a single entity/record.
 * - Must implement a handle() method that receives an optional DTO and returns a structured Outcome.
 */
interface UseCaseContract
{
    /**
     * Execute the atomic use case business logic.
     *
     * @param  ?DTO  $data  Validated input Data Transfer Object (or null for parameterless operations).
     * @return Outcome The execution outcome envelope.
     */
    public function handle(?DTO $data): Outcome;
}
