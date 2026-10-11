<?php

declare(strict_types=1);

namespace Heritage\Contracts\Repositories;

use Heritage\Parcels\Parcel;

/**
 * Interface ModelRepositoryInterface
 *
 * Explicit contract for entity repositories mapping Eloquent models to structured data envelopes (Parcels).
 *
 * Architectural Rules:
 * - Prevents leaking Eloquent model instances outside the repository layer into Features and Capabilities.
 * - All standard operations (all, find, save, destroy) consume and produce strongly-typed Parcels.
 */
interface ModelRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve all model records mapped to an array of Parcels.
     *
     * @return array<int, Parcel> Array of Parcel instances.
     */
    public function all(): array;

    /**
     * Find a record by its primary key and map to Parcel.
     *
     * @param  int|string  $id  Primary key of the record.
     * @return Parcel|null Parcel instance if found, null otherwise.
     */
    public function find(int|string $id): ?Parcel;

    /**
     * Persist or update a record using Parcel data and return a fresh instance.
     *
     * @param  Parcel  $parcel  Parcel containing payload to save.
     * @return Parcel Fresh Parcel instance from the refreshed database model.
     */
    public function save(Parcel $parcel): Parcel;

    /**
     * Retrieve paginated model records mapped to Parcels.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return mixed
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): mixed;

    /**
     * Delete a record from storage using the attached model in the Parcel or its primary key.
     *
     * @param  Parcel|int|string  $parcel  Parcel with attached model instance or ID.
     * @return bool True on success, false on failure.
     */
    public function destroy(Parcel|int|string $parcel): bool;
}
