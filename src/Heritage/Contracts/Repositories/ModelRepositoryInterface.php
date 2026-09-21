<?php

declare(strict_types=1);

namespace Heritage\Contracts\Repositories;

use Heritage\DTOs\DTO;

/**
 * Interface ModelRepositoryInterface
 *
 * Explicit contract for entity repositories mapping Eloquent models to Data Transfer Objects.
 *
 * Architectural Rules:
 * - Prevents leaking Eloquent model instances outside the repository layer into UseCases.
 * - All standard operations (all, find, save, destroy) consume and produce pure, strongly-typed DTOs.
 */
interface ModelRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve all model records mapped to an array of DTOs.
     *
     * @return DTO[] Array of DTO instances.
     */
    public function all(): array;

    /**
     * Find a record by its primary key and map to DTO.
     *
     * @param  int|string  $id  Primary key of the record.
     * @return DTO|null DTO instance if found, null otherwise.
     */
    public function find(int|string $id): ?DTO;

    /**
     * Persist or update a record using DTO data and return a fresh DTO instance.
     *
     * @param  DTO  $dto  DTO containing payload to save.
     * @return DTO Fresh DTO instance from the refreshed database model.
     */
    public function save(DTO $dto): DTO;

    /**
     * Delete a record from storage using the attached model in the DTO.
     *
     * @param  DTO  $dto  DTO with attached model instance.
     * @return bool True on success, false on failure.
     */
    public function destroy(DTO $dto): bool;
}
