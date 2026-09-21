<?php

declare(strict_types=1);

namespace Heritage\Contracts\Services;

use Heritage\DTOs\DTO;
use Heritage\Responses\Outcome;

/**
 * Interface ServiceContract
 *
 * Contract for intra-module domain services that orchestrate multiple sequential UseCases
 * within the boundaries of a single module/artifact.
 */
interface ServiceContract
{
    /**
     * Execute the service orchestration workflow.
     *
     * @param  ?DTO  $data  Input Data Transfer Object.
     * @return Outcome The unified execution outcome envelope.
     */
    public function handle(?DTO $data): Outcome;
}
