<?php

declare(strict_types=1);

namespace Heritage\Services;

use Heritage\Contracts\Services\ServiceContract;
use Heritage\DTOs\DTO;
use Heritage\Responses\Outcome;
use Heritage\Traits\Concerns\HasTransaction;

/**
 * Class Service
 *
 * Base abstract class for intra-module domain Services.
 *
 * Architectural Role & Hierarchy:
 * - Positioned directly above UseCases in the execution hierarchy.
 * - Responsible for orchestrating multiple sequential UseCases within the BOUNDARIES OF THE SAME MODULE / ARTIFACT.
 * - Example: SaveUserService orchestrates SaveUserUseCase (creating the core record) followed by
 *   SaveUserTranslationUseCase (persisting localized fields) within an atomic database transaction.
 * - Uses the HasTransaction trait to guarantee that all composed UseCase mutations either commit together
 *   or rollback atomically.
 *
 * @template TInput of DTO
 */
abstract class Service implements ServiceContract
{
    use HasTransaction;

    /**
     * Execute the intra-module orchestration workflow across multiple sequential UseCases.
     *
     * @param  ?DTO  $data  Input Data Transfer Object.
     * @return Outcome The unified outcome envelope of the multi-usecase operation.
     */
    abstract public function handle(?DTO $data): Outcome;
}
