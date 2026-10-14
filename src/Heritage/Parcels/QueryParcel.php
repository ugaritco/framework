<?php

declare(strict_types=1);

namespace Heritage\Parcels;

use Heritage\Support\Facades\Validator;
use Heritage\Validation\ValidationException;
use ReflectionClass;
use ReflectionNamedType;

/**
 * The input of a listing: a search term, a sort, a page, and the filters a subclass declares
 * as constructor parameters.
 *
 *     class IndexBranchParcel extends QueryParcel
 *     {
 *         public function __construct(public ?bool $active = null) {}      // a filter
 *
 *         public function searchable(): array { return ['slug', 'name']; }
 *         public function sortable(): array { return ['slug', 'name', 'created_at']; }
 *     }
 *
 *     IndexBranchParcel::fromQuery($request->query());   // ?search=dam&sort=-name&page=2&per_page=20&active=1
 *
 * A repository runs it with browse(): it applies the provided filters, searches the
 * searchable columns, sorts by an allowed column, and returns one page when per_page is set,
 * every record otherwise.
 */
abstract class QueryParcel extends Parcel
{
    /**
     * The term searched in the searchable columns.
     */
    public ?string $search = null;

    /**
     * The sorted column; a leading "-" sorts descending.
     */
    public ?string $sort = null;

    /**
     * The page number, used when per_page is set.
     */
    public int $page = 1;

    /**
     * Items per page; null returns every record.
     */
    public ?int $per_page = null;

    /**
     * The columns the search term is matched against; translated attributes match their
     * current-locale translation.
     *
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return [];
    }

    /**
     * The columns a listing may be sorted by.
     *
     * @return array<int, string>
     */
    public function sortable(): array
    {
        return [];
    }

    /**
     * The largest accepted per_page.
     *
     * @return int
     */
    public function maxPerPage(): int
    {
        return 100;
    }

    /**
     * Build the parcel from query string parameters: validate them (a filter against its
     * declared type, the sort against sortable(), per_page against maxPerPage()), then cast them.
     *
     * @param  array<string, mixed>  $query  The query string parameters.
     * @return static
     *
     * @throws ValidationException When a parameter is invalid.
     */
    public static function fromQuery(array $query): static
    {
        $query = array_map(fn ($value) => $value === '' ? null : $value, $query);
        $types = static::filterTypes();
        $probe = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        $sorts = [];

        foreach ($probe->sortable() as $column) {
            array_push($sorts, $column, "-{$column}");
        }

        $rules = [
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:'.implode(',', $sorts)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$probe->maxPerPage()],
        ];

        foreach ($types as $name => $type) {
            $rules[$name] = ['nullable', match ($type) {
                'bool' => 'boolean',
                'int' => 'integer',
                'float' => 'numeric',
                'array' => 'array',
                default => 'string',
            }];
        }

        $valid = Validator::make($query, $rules)->validate();

        $filters = [];

        foreach (array_intersect_key($valid, $types) as $name => $value) {
            $filters[$name] = static::cast($value, $types[$name]);
        }

        $parcel = static::fromArray($filters);
        $parcel->search = isset($valid['search']) ? trim((string) $valid['search']) : null;
        $parcel->search = $parcel->search === '' ? null : $parcel->search;
        $parcel->sort = $valid['sort'] ?? null;
        $parcel->page = isset($valid['page']) ? (int) $valid['page'] : 1;
        $parcel->per_page = isset($valid['per_page']) ? (int) $valid['per_page'] : null;

        return $parcel;
    }

    /**
     * The filters to apply: the declared filters that were provided (or, for a parcel created
     * with new, that are set), without nulls.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $values = array_intersect_key(get_object_vars($this), static::filterTypes());

        if ($this->provided !== null) {
            $values = array_intersect_key($values, array_flip($this->provided));
        }

        return array_filter($values, fn ($value) => $value !== null);
    }

    /**
     * The sorted column, without its direction.
     *
     * @return string|null
     */
    public function sortColumn(): ?string
    {
        return $this->sort === null ? null : ltrim($this->sort, '-');
    }

    /**
     * The sort direction: "desc" for a leading "-", "asc" otherwise.
     *
     * @return string
     */
    public function sortDirection(): string
    {
        return str_starts_with((string) $this->sort, '-') ? 'desc' : 'asc';
    }

    /**
     * Whether the listing returns one page rather than every record.
     *
     * @return bool
     */
    public function paginates(): bool
    {
        return $this->per_page !== null;
    }

    /**
     * The declared filters (constructor parameters) and their builtin types.
     *
     * @return array<string, string>
     */
    protected static function filterTypes(): array
    {
        $constructor = (new ReflectionClass(static::class))->getConstructor();
        $types = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $types[$parameter->getName()] = $type instanceof ReflectionNamedType && $type->isBuiltin() ? $type->getName() : 'string';
        }

        return $types;
    }

    /**
     * Cast a validated query value to a filter's type.
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return mixed
     */
    protected static function cast(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            'array' => (array) $value,
            default => is_scalar($value) ? (string) $value : $value,
        };
    }
}
