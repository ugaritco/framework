<?php

declare(strict_types=1);

namespace Heritage\Repositories;

use Exception;
use Heritage\Contracts\Repositories\ModelRepositoryInterface;
use Heritage\Database\Eloquent\Model;
use Heritage\DTOs\DTO;
use InvalidArgumentException;
use LogicException;
use ReflectionException;

/**
 * Class ModelRepository
 *
 * Base repository implementation providing standard CRUD operations mapping Eloquent models to DTOs.
 *
 * Architectural Goals:
 * 1. Decouple Eloquent models from the business layer; UseCases interact solely with pure DTOs.
 * 2. Provide standard CRUD operations (all, find, save, destroy, snapshotById) uniformly across all entities.
 * 3. Support automatic create/update detection: if a DTO has an attached model instance, it updates;
 *    otherwise, it creates a new record.
 *
 * @template TModel of Model
 * @template TDTO of DTO
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
     * Convert a given Eloquent Model instance into its corresponding DTO.
     *
     * Each concrete repository must define this mapping for its specific entity.
     *
     * @param  Model  $model  The Eloquent model instance to convert.
     * @return DTO The corresponding Data Transfer Object.
     */
    abstract protected function mapToDTO(Model $model): DTO;

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
     * Retrieve all models from the database mapped to a list of DTOs.
     *
     * @return array<int, DTO> List of DTOs representing the database records.
     */
    public function all(): array
    {
        // 1. Build a new query and fetch all records from the database
        // 2. Map each retrieved model instance to its corresponding DTO via mapToDTO()
        // 3. Return a clean array of DTO instances
        return $this->model->newQuery()->get()
            ->map(fn ($model) => $this->mapToDTO($model))
            ->all();
    }

    /**
     * Save or update a model using data provided by the DTO.
     *
     * Flow:
     * 1. Convert DTO data to a raw associative array.
     * 2. Filter out null values if shouldFilterNulls() is enabled to avoid overwriting existing columns.
     * 3. Use attached model (Update) or instantiate a new model (Create).
     * 4. Fill model attributes and persist to database.
     * 5. Reconstruct and return a fresh DTO instance from the refreshed database model.
     *
     * @param  DTO  $dto  Data Transfer Object containing fields to save.
     * @return DTO Fresh DTO instance reflecting the persisted record.
     *
     * @throws ReflectionException
     */
    public function save(DTO $dto): DTO
    {
        // Step 1: Convert DTO to associative array
        $data = $dto->toArray();

        // Step 2: Filter out null values if the DTO requests null filtering
        if ($dto->shouldFilterNulls()) {
            $data = array_filter($data, fn ($value) => ! is_null($value));
        }

        // Step 3: Determine target model; use attached instance if present (Update), else create new (Create)
        $model = $dto->getModel() ?? $this->model->newInstance();

        // Step 4: Fill model attributes with prepared data
        $model->fill($data);

        // Step 5: Persist changes to database
        $model->save();

        // Step 6: Construct and return a fresh DTO instance from the refreshed database record
        return $dto::fromModel($model->fresh(), true);
    }

    /**
     * Find a model by its primary key and map it to a DTO.
     *
     * @param  int|string  $id  Primary key value of the target record.
     * @return DTO|null DTO instance if found, or null if the record does not exist.
     */
    public function find(int|string $id): ?DTO
    {
        // Execute primary key lookup on the model
        $model = $this->model->newQuery()->find($id);

        // Return null safely if record was not found
        if (! $model) {
            return null;
        }

        // Map model to DTO and return
        return $this->mapToDTO($model);
    }

    /**
     * Take a snapshot of an entity by ID and map it to a specialized Snapshot DTO.
     *
     * @param  int|string  $id  Primary key of the entity.
     * @param  class-string<DTO>  $snapshotDtoClass  Target snapshot DTO class.
     * @return DTO|null
     *
     * @throws ReflectionException|InvalidArgumentException
     */
    public function snapshotById(int|string $id, string $snapshotDtoClass): ?DTO
    {
        // 1. Fetch model from database by primary key
        $model = $this->model->newQuery()->find($id);

        // 2. Return null if entity was not found
        if (! $model) {
            return null;
        }

        // 3. Ensure the target class is a valid subclass of DTO
        if (! is_subclass_of($snapshotDtoClass, DTO::class)) {
            throw new InvalidArgumentException(
                'Snapshot DTO must extend '.DTO::class
            );
        }

        // 4. Construct snapshot DTO from the model instance
        return $snapshotDtoClass::fromModel($model);
    }

    /**
     * Delete a model from the database using the attached instance in the DTO.
     *
     * @param  DTO  $dto  DTO with attached model to delete.
     * @return bool True on successful deletion, false on failure.
     *
     * @throws LogicException If no model instance is attached to the DTO.
     */
    public function destroy(DTO $dto): bool
    {
        // Step 1: Extract the attached model instance from the DTO
        $model = $dto->getModel();

        // Step 2: Validate model presence; cannot delete without a concrete instance
        if (! $model) {
            throw new LogicException('Cannot delete a model without an attached instance.');
        }

        try {
            // Step 3: Check whether force delete or standard/soft delete is requested
            if (method_exists($model, 'forceDelete') && ($dto->force ?? false)) {
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
