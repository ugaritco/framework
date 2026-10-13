<?php

declare(strict_types=1);

namespace Heritage\Contracts\Repositories;

use Heritage\Contracts\Pagination\LengthAwarePaginator;
use Heritage\Database\Eloquent\Collection;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\ModelNotFoundException;
use Heritage\Parcels\Parcel;

/**
 * Interface ModelRepositoryInterface
 *
 * Contract for entity repositories: Parcels come in, Eloquent models come out.
 *
 * Architectural Rules:
 * - Queries and persistence stay in the repository; capabilities never build queries.
 * - Input is a Parcel (its provided attributes are persisted); output is the model, which the
 *   capability hands to a Resource for presentation.
 *
 * @template TModel of Model
 */
interface ModelRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve all records.
     *
     * @return Collection<int, TModel>
     */
    public function all(): Collection;

    /**
     * Retrieve the records of one page.
     *
     * @param  int  $perPage  Items per page.
     * @param  array<int, string>  $columns  Columns to select.
     * @param  string  $pageName  Query string parameter holding the page number.
     * @param  int|null  $page  Explicit page number.
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator;

    /**
     * Find a record by its primary key.
     *
     * @param  int|string  $id  Primary key of the record.
     * @return TModel|null The model, or null when it does not exist.
     */
    public function find(int|string $id): ?Model;

    /**
     * Find a record by its primary key or fail.
     *
     * @param  int|string  $id  Primary key of the record.
     * @return TModel
     *
     * @throws ModelNotFoundException When the record does not exist.
     */
    public function findOrFail(int|string $id): Model;

    /**
     * Create a record, or update the model attached to the Parcel, from the Parcel's attributes.
     *
     * @param  Parcel  $parcel  The attributes to persist (see Parcel::toAttributes()).
     * @return TModel The persisted model, freshly loaded.
     */
    public function save(Parcel $parcel): Model;

    /**
     * Delete a record: the model attached to the Parcel or its id, a model, or a primary key.
     * A Parcel with a true `force` property force-deletes a soft-deletable model.
     *
     * @param  Parcel|Model|int|string  $target  What to delete.
     * @return bool True when a record was deleted, false when the key matches no record.
     */
    public function destroy(Parcel|Model|int|string $target): bool;
}
