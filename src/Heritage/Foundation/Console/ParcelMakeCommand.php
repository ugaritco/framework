<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\InspectsModel;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:parcel')]
class ParcelMakeCommand extends GeneratorCommand
{
    use InspectsModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:parcel
                    {name : The name of the parcel}
                    {--artifact= : The target modular artifact name}
                    {--query : Create a QueryParcel for a listing (search, sort, page, filters)}
                    {--operation= : Generate the fields of an operation: create, show, edit, save or destroy}
                    {--model= : The model whose columns become the fields (relative to Models/ or fully-qualified)}
                    {--f|force : Create the class even if the parcel already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new parcel structured data class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Parcel';

    /**
     * The operations whose fields can be generated.
     *
     * @var array<int, string>
     */
    protected array $operations = ['create', 'show', 'edit', 'save', 'destroy'];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath($this->option('query') ? '/stubs/parcel.query.stub' : '/stubs/parcel.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->ugarit->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        if (! str_ends_with($name, 'Parcel')) {
            $name .= 'Parcel';
        }

        return parent::qualifyClass($name);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Parcels';
    }

    /**
     * Build the parcel with the fields of its operation or listing.
     *
     * @param  string  $name
     * @return string
     *
     * @throws InvalidArgumentException When the operation is unknown.
     */
    protected function buildClass($name)
    {
        $model = $this->modelClass();
        $modelName = $model ? class_basename($model) : 'record';

        if ($this->option('query')) {
            [$fields, $searchable, $sortable] = $this->queryFields($model);

            return $this->render(parent::buildClass($name), $fields, [
                '{{ model }}' => $modelName,
                '{{ searchable }}' => $this->quoteList($searchable),
                '{{ sortable }}' => $this->quoteList($sortable),
            ]);
        }

        $operation = $this->option('operation');

        if ($operation !== null && ! in_array($operation, $this->operations, true)) {
            throw new InvalidArgumentException("Unknown parcel operation [{$operation}]; use ".implode(', ', $this->operations).'.');
        }

        $stub = $this->render(parent::buildClass($name), $this->operationFields($operation, $model), [
            '{{ description }}' => $this->description($operation, $modelName),
        ]);

        return $operation === null
            ? preg_replace('/__construct\((\r?\n)    \) \{\}/', '__construct($1        // public ?int $id = null,$1    ) {}', $stub)
            : $stub;
    }

    /**
     * The fields of an operation: [name => [type, default, description]].
     *
     * @param  string|null  $operation
     * @param  string|null  $model
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    protected function operationFields(?string $operation, ?string $model): array
    {
        $id = ['id' => ['?int', 'null', 'The record identifier.']];

        return match ($operation) {
            null, 'create' => [],
            'show', 'edit' => $id,
            'destroy' => [...$id, 'force' => ['bool', 'false', 'Whether to delete it permanently.']],
            'save' => [...$id, ...$this->columnFields($model)],
        };
    }

    /**
     * The model's columns as nullable fields, then its translations payload.
     *
     * @param  string|null  $model
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    protected function columnFields(?string $model): array
    {
        $fields = [];

        foreach ($this->modelColumns($model) as $column => $type) {
            $fields[$column] = ["?{$type}", 'null', "The {$column} column."];
        }

        if ($this->modelTranslatable($model) !== []) {
            $fields['translations'] = ['?array', 'null', 'Translated attributes keyed by locale code (["ar" => ["name" => ...]]).'];
        }

        return $fields;
    }

    /**
     * The filters, searchable and sortable columns of a listing, read from the model.
     *
     * @param  string|null  $model
     * @return array{0: array<string, array{0: string, 1: string, 2: string}>, 1: array<int, string>, 2: array<int, string>}
     */
    protected function queryFields(?string $model): array
    {
        $filters = [];
        $text = $this->modelTranslatable($model);

        foreach ($this->modelColumns($model) as $column => $type) {
            if ($type === 'bool' || str_ends_with($column, '_id')) {
                $filters[$column] = ["?{$type}", 'null', "Only the records with this {$column}."];
            } elseif ($type === 'string') {
                $text[] = $column;
            }
        }

        return [$filters, $text, [...$text, 'created_at']];
    }

    /**
     * Fill the constructor fields, their docblock and the other placeholders.
     *
     * @param  string  $stub
     * @param  array<string, array{0: string, 1: string, 2: string}>  $fields
     * @param  array<string, string>  $replacements
     * @return string
     */
    protected function render(string $stub, array $fields, array $replacements): string
    {
        $docs = '';
        $parameters = '';

        foreach ($fields as $field => [$type, $default, $description]) {
            $docs .= "     * @param  {$this->docType($type)}  \${$field}  {$description}\n";
            $parameters .= "        public {$type} \${$field} = {$default},\n";
        }

        return str_replace(
            ['{{ docs }}', '{{ fields }}', ...array_keys($replacements)],
            [$docs === '' ? '' : "     *\n{$docs}", $parameters, ...array_values($replacements)],
            $stub
        );
    }

    /**
     * The docblock type of a field type ("?int" becomes "int|null").
     *
     * @param  string  $type
     * @return string
     */
    protected function docType(string $type): string
    {
        return str_starts_with($type, '?') ? substr($type, 1).'|null' : $type;
    }

    /**
     * Render a list of column names as quoted PHP strings.
     *
     * @param  array<int, string>  $columns
     * @return string
     */
    protected function quoteList(array $columns): string
    {
        return implode(', ', array_map(fn (string $column) => "'{$column}'", array_values(array_unique($columns))));
    }

    /**
     * The summary line of the parcel's docblock.
     *
     * @param  string|null  $operation
     * @param  string  $model
     * @return string
     */
    protected function description(?string $operation, string $model): string
    {
        return match ($operation) {
            'create' => "The context of the {$model} creation form.",
            'show' => "The {$model} to show: an attached model, or its id.",
            'edit' => "The {$model} to edit: an attached model, or its id.",
            'save' => "The {$model} attributes to save: an attached model is updated, otherwise one is created.",
            'destroy' => "The {$model} to delete: an attached model or its id, and whether to delete it permanently.",
            default => 'Structured data envelope for transporting typed payloads.',
        };
    }
}
