<?php

declare(strict_types=1);

namespace Heritage\Repositories;

use Heritage\Contracts\Pagination\LengthAwarePaginator;
use Heritage\Contracts\Repositories\ModelRepositoryInterface;
use Heritage\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\Collection;
use Heritage\Database\Eloquent\Model;
use Heritage\Parcels\Parcel;
use Heritage\Parcels\QueryParcel;
use Heritage\Support\Str;
use InvalidArgumentException;
use LogicException;
use ReflectionException;

/**
 * Class ModelRepository
 *
 * Base repository implementation: Parcels come in, Eloquent models come out.
 *
 * Architectural Goals:
 * 1. Keep queries and persistence out of Features and Capabilities.
 * 2. Provide standard operations (all, paginate, find, findOrFail, save, destroy) uniformly across entities.
 * 3. Detect create/update automatically: a Parcel with an attached model updates it, otherwise
 *    a new record is created.
 *
 * @template TModel of Model
 *
 * @implements ModelRepositoryInterface<TModel>
 */
abstract class ModelRepository implements ModelRepositoryInterface
{
    /**
     * The underlying Eloquent model instance.
     *
     * @var TModel
     */
    protected Model $model;

    /**
     * Initialize the repository with an Eloquent model instance.
     *
     * @param  TModel  $model  The Eloquent model instance bound to this repository.
     */
    public function __construct(Model $model)
    {
        // Store the model instance for queries and persistence
        $this->model = $model;
    }

    /**
     * Get the underlying Eloquent model instance.
     *
     * @return TModel
     */
    public function getModel(): Model
    {
        // Return internal model instance
        return $this->model;
    }

    /**
     * Start a new query on the repository's model; override to apply default scopes or eager loads.
     *
     * @return Builder
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Retrieve all records.
     *
     * @return Collection<int, TModel>
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Retrieve the records of one page.
     *
     * @param  int  $perPage  Items per page (default: 15).
     * @param  array<int, string>  $columns  Columns to select.
     * @param  string  $pageName  Query string parameter holding the page number.
     * @param  int|null  $page  Explicit page number.
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Run a listing: apply the parcel's filters, search its searchable columns, sort by its
     * sort, then return one page when it sets per_page, every record otherwise.
     *
     * A filter calls filter{Name}(Builder $query, mixed $value) when the repository defines
     * it; otherwise it matches its column (an array matches any of its values). Translated
     * attributes are filtered, searched and sorted through their current-locale translation.
     *
     * @param  QueryParcel  $parcel  The listing criteria.
     * @return Collection<int, TModel>|LengthAwarePaginator
     */
    public function browse(QueryParcel $parcel): Collection|LengthAwarePaginator
    {
        $query = $this->query();

        foreach ($parcel->filters() as $name => $value) {
            $this->applyFilter($query, $name, $value);
        }

        if ($parcel->search !== null && $parcel->searchable() !== []) {
            $this->applySearch($query, $parcel->search, $parcel->searchable());
        }

        if ($parcel->sortColumn() !== null) {
            $this->applySort($query, $parcel->sortColumn(), $parcel->sortDirection());
        }

        return $parcel->paginates()
            ? $query->paginate($parcel->per_page, ['*'], 'page', $parcel->page)
            : $query->get();
    }

    /**
     * Constrain a listing by one filter.
     *
     * @param  Builder  $query
     * @param  string  $name  The filter (a column or translated attribute by default).
     * @param  mixed  $value  Its value; an array matches any of its values.
     * @return void
     */
    protected function applyFilter(Builder $query, string $name, mixed $value): void
    {
        $method = 'filter'.Str::studly($name);

        if (method_exists($this, $method)) {
            $this->{$method}($query, $value);

            return;
        }

        $model = $query->getModel();

        if ($this->isTranslated($model, $name)) {
            $resolver = $model::localeResolver();

            is_array($value)
                ? $query->whereHas('translations', fn (Builder $q) => $q->whereIn($name, $value)
                    ->where($model->getTranslationLocaleColumn(), $resolver->toStorage($resolver->current())))
                : $query->whereTranslation($name, $value);

            return;
        }

        $column = $model->qualifyColumn($name);

        is_array($value) ? $query->whereIn($column, $value) : $query->where($column, $value);
    }

    /**
     * Constrain a listing to records whose searchable columns contain the term.
     *
     * @param  Builder  $query
     * @param  string  $term
     * @param  array<int, string>  $columns
     * @return void
     */
    protected function applySearch(Builder $query, string $term, array $columns): void
    {
        $model = $query->getModel();
        $pattern = "%{$term}%";

        $query->where(function (Builder $query) use ($model, $columns, $pattern) {
            foreach ($columns as $column) {
                $this->isTranslated($model, $column)
                    ? $query->orWhere(fn (Builder $q) => $q->whereTranslationLike($column, $pattern))
                    : $query->orWhere($model->qualifyColumn($column), 'like', $pattern);
            }
        });
    }

    /**
     * Order a listing by a column or a translated attribute.
     *
     * @param  Builder  $query
     * @param  string  $column
     * @param  string  $direction  "asc" or "desc".
     * @return void
     */
    protected function applySort(Builder $query, string $column, string $direction): void
    {
        $this->isTranslated($query->getModel(), $column)
            ? $query->orderByTranslation($column, $direction)
            : $query->orderBy($query->getModel()->qualifyColumn($column), $direction);
    }

    /**
     * Whether an attribute of the model is stored in its translations.
     *
     * @param  Model  $model
     * @param  string  $attribute
     * @return bool
     */
    protected function isTranslated(Model $model, string $attribute): bool
    {
        return method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($attribute);
    }

    /**
     * Find a record by its primary key.
     *
     * @param  int|string  $id  Primary key value of the target record.
     * @return TModel|null The model, or null when it does not exist.
     */
    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * Find a record by its primary key or fail.
     *
     * @param  int|string  $id  Primary key value of the target record.
     * @return TModel
     *
     * @throws \Heritage\Database\Eloquent\ModelNotFoundException When the record does not exist.
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Create a record, or update the model attached to the Parcel, from the Parcel's attributes.
     *
     * Flow:
     * 1. Take the attributes the Parcel was given (Parcel::toAttributes()): a key given as null
     *    clears its column, a key never given is left untouched.
     * 2. Use the attached model (update) or a new instance (create).
     * 3. Fill, save, and return the model freshly loaded from the database.
     *
     * @param  Parcel  $parcel  Parcel containing the attributes to save.
     * @return TModel The persisted model.
     */
    public function save(Parcel $parcel): Model
    {
        $model = $parcel->getModel() ?? $this->model->newInstance();

        $model->fill($parcel->toAttributes())->save();

        return $model->fresh();
    }

    /**
     * Take a snapshot of an entity by ID and map it to a specialized Snapshot Parcel.
     *
     * @param  int|string  $id  Primary key of the entity.
     * @param  class-string<Parcel>  $snapshotClass  Target snapshot class.
     * @return Parcel|null The snapshot, or null when the entity does not exist.
     *
     * @throws ReflectionException|InvalidArgumentException
     */
    public function snapshotById(int|string $id, string $snapshotClass): ?Parcel
    {
        if (! is_subclass_of($snapshotClass, Parcel::class)) {
            throw new InvalidArgumentException('Snapshot class must extend '.Parcel::class);
        }

        $model = $this->find($id);

        return $model ? $snapshotClass::fromModel($model) : null;
    }

    /**
     * Delete a record: the model attached to the Parcel or its id, a model, or a primary key.
     *
     * A Parcel with a true `force` property force-deletes a soft-deletable model. Database
     * errors are not caught: they reach the capability, which reports them.
     *
     * @param  Parcel|Model|int|string  $target  What to delete.
     * @return bool True when a record was deleted, false when the key matches no record.
     *
     * @throws LogicException When a Parcel carries neither a model nor an id.
     */
    public function destroy(Parcel|Model|int|string $target): bool
    {
        $force = $target instanceof Parcel && (bool) ($target->force ?? false);

        $model = match (true) {
            $target instanceof Model => $target,
            $target instanceof Parcel => $target->getModel() ?? (isset($target->id)
                ? $this->find($target->id)
                : throw new LogicException('Cannot delete without an attached model or an id.')),
            default => $this->find($target),
        };

        if (! $model) {
            return false;
        }

        return (bool) ($force && method_exists($model, 'forceDelete') ? $model->forceDelete() : $model->delete());
    }
}
