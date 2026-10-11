<?php

declare(strict_types=1);

namespace Heritage\Repositories;

use Exception;
use Heritage\Contracts\Repositories\ModelRepositoryInterface;
use Heritage\Database\Eloquent\Model;
use Heritage\Parcels\Parcel;
use InvalidArgumentException;
use LogicException;
use ReflectionException;

/**
 * Class ModelRepository
 *
 * Base repository implementation providing standard CRUD operations mapping Eloquent models to Parcels.
 *
 * Architectural Goals:
 * 1. Decouple Eloquent models from the business layer; Features and Capabilities interact solely with pure Parcels.
 * 2. Provide standard CRUD operations (all, find, save, destroy, snapshotById) uniformly across all entities.
 * 3. Support automatic create/update detection: if a Parcel has an attached model instance, it updates;
 *    otherwise, it creates a new record.
 *
 * @template TModel of Model
 * @template TParcel of Parcel
 */
abstract class ModelRepository implements ModelRepositoryInterface
{
    /**
     * The underlying Eloquent model instance.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Initialize the repository with an Eloquent model instance.
     *
     * @param  Model  $model  The Eloquent model instance bound to this repository.
     */
    public function __construct(Model $model)
    {
        // Store the model instance for queries and persistence
        $this->model = $model;
    }

    /**
     * Convert a given Eloquent Model instance into its corresponding Parcel.
     *
     * @param  Model  $model  The Eloquent model instance to convert.
     * @return Parcel The corresponding Parcel representation.
     */
    protected function mapToParcel(Model $model): Parcel
    {
        throw new LogicException('Repository must implement mapToParcel().');
    }

    /**
     * Get the underlying Eloquent model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        // Return internal model instance
        return $this->model;
    }

    /**
     * Retrieve all models from the database mapped to a list of Parcels.
     *
     * @return array<int, Parcel> List of Parcels representing the database records.
     */
    public function all(): array
    {
        // 1. Fetch all records from the database
        // 2. Map each retrieved model instance to its corresponding Parcel via mapToParcel()
        // 3. Return a clean array of Parcel instances
        return $this->model->newQuery()->get()
            ->map(fn ($model) => $this->mapToParcel($model))
            ->all();
    }

    /**
     * Retrieve paginated model records mapped to Parcels.
     *
     * @param  int  $perPage  Items per page (default: 15).
     * @param  array  $columns  Columns to select.
     * @param  string  $pageName  Query parameter name for pagination page.
     * @param  int|null  $page  Explicit page number.
     * @return mixed LengthAwarePaginator with items mapped to Parcels.
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): mixed
    {
        $paginator = $this->model->newQuery()->paginate($perPage, $columns, $pageName, $page);

        return $paginator->through(fn ($model) => $this->mapToParcel($model));
    }

    /**
     * Save or update a model using data provided by the Parcel.
     *
     * Flow:
     * 1. Convert parcel data to a raw associative array.
     * 2. Filter out null values if shouldFilterNulls() is enabled to avoid overwriting existing columns.
     * 3. Use attached model (Update) or instantiate a new model (Create).
     * 4. Fill model attributes and persist to database.
     * 5. Reconstruct and return a fresh Parcel instance from the refreshed database model.
     *
     * @param  Parcel  $parcel  Parcel containing fields to save.
     * @return Parcel Fresh instance reflecting the persisted record.
     *
     * @throws ReflectionException
     */
    public function save(Parcel $parcel): Parcel
    {
        // Step 1: Convert Parcel to associative array
        $data = $parcel->toArray();

        // Step 2: Filter out null values if null filtering is requested
        if ($parcel->shouldFilterNulls()) {
            $data = array_filter($data, fn ($value) => ! is_null($value));
        }

        // Step 3: Determine target model; use attached instance if present (Update), else create new (Create)
        $model = $parcel->getModel() ?? $this->model->newInstance();

        // Step 4: Fill model attributes with prepared data
        $model->fill($data);

        // Step 5: Persist changes to database
        $model->save();

        // Step 6: Construct and return a fresh Parcel instance from the refreshed database record
        return $parcel::fromModel($model->fresh(), true);
    }

    /**
     * Find a model by its primary key and map it to a Parcel.
     *
     * @param  int|string  $id  Primary key value of the target record.
     * @return Parcel|null Parcel instance if found, or null if the record does not exist.
     */
    public function find(int|string $id): ?Parcel
    {
        // Execute primary key lookup on the model
        $model = $this->model->newQuery()->find($id);

        // Return null safely if record was not found
        if (! $model) {
            return null;
        }

        // Map model to parcel and return
        return $this->mapToParcel($model);
    }

    /**
     * Take a snapshot of an entity by ID and map it to a specialized Snapshot Parcel.
     *
     * @param  int|string  $id  Primary key of the entity.
     * @param  class-string<Parcel>  $snapshotClass  Target snapshot class.
     * @return Parcel|null
     *
     * @throws ReflectionException|InvalidArgumentException
     */
    public function snapshotById(int|string $id, string $snapshotClass): ?Parcel
    {
        // 1. Fetch model from database by primary key
        $model = $this->model->newQuery()->find($id);

        // 2. Return null if entity was not found
        if (! $model) {
            return null;
        }

        // 3. Ensure the target class is a valid subclass of Parcel
        if (! is_subclass_of($snapshotClass, Parcel::class)) {
            throw new InvalidArgumentException(
                'Snapshot class must extend '.Parcel::class
            );
        }

        // 4. Construct snapshot instance from the model instance
        return $snapshotClass::fromModel($model);
    }

    /**
     * Delete a model from the database using the attached instance in the Parcel or its primary key.
     *
     * @param  Parcel|int|string  $parcel  Parcel with attached model to delete, or model primary key.
     * @return bool True on successful deletion, false on failure.
     *
     * @throws LogicException If no model instance or ID is available.
     */
    public function destroy(Parcel|int|string $parcel): bool
    {
        // Case 1: Primary key provided directly
        if (is_int($parcel) || is_string($parcel)) {
            $model = $this->model->newQuery()->find($parcel);

            if (! $model) {
                return false;
            }

            return (bool) $model->delete();
        }

        // Case 2: Parcel provided
        $model = $parcel->getModel() ?? (isset($parcel->id) ? $this->model->newQuery()->find($parcel->id) : null);

        // Validate model presence
        if (! $model) {
            throw new LogicException('Cannot delete a model without an attached instance or valid ID.');
        }

        try {
            // Check whether force delete or standard/soft delete is requested
            if (method_exists($model, 'forceDelete') && ($parcel->force ?? false)) {
                // Execute hard delete from database table
                $model->forceDelete();
            } else {
                // Execute standard delete (or soft delete if model supports it)
                $model->delete();
            }

            // Return success
            return true;
        } catch (Exception) {
            // Return failure if an exception occurs during deletion
            return false;
        }
    }
}
