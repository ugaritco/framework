<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\InspectsModel;
use Heritage\Foundation\Console\Ui\Blueprint;
use Heritage\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function Ugarit\Prompts\confirm;
use function Ugarit\Prompts\select;
use function Ugarit\Prompts\text;

#[AsCommand(name: 'make:request')]
class RequestMakeCommand extends GeneratorCommand
{
    use InspectsModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:request
                    {name : The name of the form request class}
                    {--type= : The architectural layer (capability, feature, standard)}
                    {--capability : Create a Capability-layer execution FormRequest}
                    {--feature : Create a Feature-layer workflow FormRequest}
                    {--artifact= : The target modular artifact name}
                    {--model= : The entity/model name this request belongs to}
                    {--t|translatable : Declare translatable fields ($translatable) in the request}
                    {--fields= : Comma-separated translatable field names (e.g. name,description)}
                    {--action= : The Capability or Feature the request feeds, relative to the root namespace (e.g. Capabilities/Branch/SaveBranchCapability)}
                    {--operation= : Generate the rules of an operation from the --model columns: store, update or destroy}
                    {--nullable= : Comma-separated fields the rules accept empty (e.g. email,published_at)}
                    {--f|force : Create the class even if the request already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new form request class with optional artifact, layer, and translatable support';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Request';

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Heritage\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        // 1. Resolve target location (Artifact or Application) interactively if not provided
        $this->promptForArtifact('Where should this FormRequest be created?');

        // 2. Resolve architectural layer (Capability, Feature, Standard)
        $this->resolveArchitecturalLayer();

        // 3. Resolve translatable attributes support
        $this->resolveTranslatableSupport();

        return parent::handle();
    }

    /**
     * Resolve the architectural layer for the request.
     *
     * @return void
     */
    protected function resolveArchitecturalLayer(): void
    {
        if ($this->option('capability')) {
            $this->input->setOption('type', 'capability');
            return;
        }

        if ($this->option('feature')) {
            $this->input->setOption('type', 'feature');
            return;
        }

        if ($this->option('type')) {
            return;
        }

        if ($this->input->isInteractive() && function_exists('\\Ugarit\\Prompts\\select')) {
            $choice = select(
                label: 'Which architectural layer is this FormRequest intended for?',
                options: [
                    'capability' => 'Capability Layer (e.g. Branch/Capabilities/StoreBranchRequest)',
                    'feature'    => 'Feature Orchestration Layer (e.g. Branch/Features/StoreBranchRequest)',
                    'standard'   => 'Standard / Direct Layer (e.g. Http/Requests/StoreBranchRequest)',
                ],
                default: 'capability',
            );

            $this->input->setOption('type', $choice);
        }
    }

    /**
     * Resolve whether this request supports translatable attributes.
     *
     * @return void
     */
    protected function resolveTranslatableSupport(): void
    {
        $hasTranslatableFlag = $this->option('translatable') || $this->option('fields');

        if ($hasTranslatableFlag) {
            $this->input->setOption('translatable', true);
            return;
        }

        if ($this->option('operation') && $this->modelClass()) {
            $translatable = $this->modelTranslatable($this->modelClass());

            if ($translatable !== [] && $this->option('operation') !== 'destroy' && $this->option('type') !== 'capability') {
                $this->input->setOption('translatable', true);
                $this->input->setOption('fields', implode(',', $translatable));
            }

            return;
        }

        if ($this->input->isInteractive() && function_exists('\\Ugarit\\Prompts\\confirm')) {
            $isTranslatable = confirm(
                label: 'Does this FormRequest validate multilingual / translatable fields?',
                default: true,
            );

            if ($isTranslatable) {
                $this->input->setOption('translatable', true);

                if (! $this->option('fields') && function_exists('\\Ugarit\\Prompts\\text')) {
                    $fields = text(
                        label: 'What translatable fields should be configured? (comma-separated)',
                        placeholder: 'name, description',
                        default: 'name',
                    );

                    $this->input->setOption('fields', $fields);
                }
            }
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->confirmsDestroy()) {
            return $this->resolveStubPath('/stubs/request.destroy.stub');
        }

        if ($this->isTranslatable()) {
            return $this->resolveStubPath('/stubs/request.translatable.stub');
        }

        return $this->resolveStubPath('/stubs/request.stub');
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
     * Determine if the request being generated is translatable.
     *
     * @return bool
     */
    protected function isTranslatable(): bool
    {
        return (bool) ($this->option('translatable') || $this->option('fields'));
    }

    /**
     * Parse the class name and format according to the root namespace and layer hierarchy.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');
        $name = str_replace('/', '\\', $name);

        // Ensure class name ends with "Request"
        if (! str_ends_with($name, 'Request')) {
            $name .= 'Request';
        }

        $layer = strtolower((string) ($this->option('type') ?? ''));
        $layerSegment = match ($layer) {
            'capability' => 'Capabilities',
            'feature'    => 'Features',
            default      => null,
        };

        // If entity and layer need to be organized
        if ($layerSegment && ! str_contains($name, "\\{$layerSegment}\\") && ! str_starts_with($name, "{$layerSegment}\\")) {
            $parts = explode('\\', $name);
            $className = array_pop($parts);

            // Determine entity name
            $entity = ! empty($parts) ? implode('\\', $parts) : ($this->option('model') ?? $this->extractEntityFromClassName($className));

            if ($entity) {
                $name = "{$entity}\\{$layerSegment}\\{$className}";
            } else {
                $name = "{$layerSegment}\\{$className}";
            }
        }

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\'.$name
        );
    }

    /**
     * Extract probable entity name from request class name (e.g. StoreBranchRequest -> Branch).
     *
     * @param  string  $className
     * @return string|null
     */
    protected function extractEntityFromClassName(string $className): ?string
    {
        $prefixes = ['Store', 'Update', 'Destroy', 'Delete', 'Show', 'Index', 'Save', 'Create', 'Edit', 'Patch', 'Put'];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($className, $prefix) && str_ends_with($className, 'Request')) {
                $entity = substr($className, strlen($prefix), -7);
                if (! empty($entity)) {
                    return $entity;
                }
            }
        }

        return null;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Requests';
    }

    /**
     * Build the class with the given name and stub replacements.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Heritage\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        if ($this->confirmsDestroy()) {
            return $this->buildDestroyClass($name);
        }

        $classContent = $this->replaceForAction(parent::buildClass($name));
        $columnRules = $this->operationRules();

        if ($columnRules !== [] && ! $this->isTranslatable()) {
            $classContent = preg_replace('/^            \/\/(\r?\n)/m', implode("\n", $columnRules).'$1', $classContent, 1);
        }

        if ($this->isTranslatable()) {
            $fieldsInput = (string) ($this->option('fields') ?: 'name');
            $fields = array_values(array_filter(array_map('trim', explode(',', $fieldsInput))));

            if (empty($fields)) {
                $fields = ['name'];
            }

            $fieldsArrayString = implode(', ', array_map(fn ($f) => "'{$f}'", $fields));

            $columns = $this->translationColumns();
            $rulesLines = [];

            foreach ($fields as $field) {
                $column = $columns[$field] ?? null;
                $rule = match (true) {
                    in_array($field, ['name', 'title'], true) => "'{$field}' => 'required|string|min:2|max:64',",
                    $field === 'slug' => "'{$field}' => 'required|string|min:2|max:64|unique:table,slug',",
                    in_array($field, ['description', 'body', 'content', 'notes'], true) => "'{$field}' => 'nullable|string',",
                    $column !== null && in_array(strtolower($column['type_name']), ['varchar', 'char', 'character varying', 'nvarchar', 'string'], true) => "'{$field}' => 'required|string|max:255',",
                    default => "'{$field}' => 'required|string',",
                };

                if ($column !== null && $column['nullable']) {
                    $rule = str_replace("=> 'required|", "=> 'nullable|", $rule);
                }

                $rulesLines[] = '            '.$this->allowEmpty($field, $rule);
            }

            $rulesString = implode("\n", [...$columnRules, ...$rulesLines]);

            $classContent = str_replace(
                ['{{ translatable_fields }}', '{{translatable_fields}}', '{{ rules }}', '{{rules}}'],
                [$fieldsArrayString, $fieldsArrayString, $rulesString, $rulesString],
                $classContent
            );
        }

        return $classContent;
    }

    /**
     * Whether the request validates the deletion of an existing --model, which must then be
     * confirmed by typing one of its fields.
     *
     * @return bool
     */
    protected function confirmsDestroy(): bool
    {
        return $this->option('operation') === 'destroy' && $this->modelInstance($this->modelClass()) !== null;
    }

    /**
     * Build the destroy request of the --model: the typed `confirmation` must match the
     * confirmation field of the record bound to the route.
     *
     * @param  string  $name  The fully-qualified request name.
     * @return string The generated source.
     */
    protected function buildDestroyClass(string $name): string
    {
        $model = $this->modelClass();
        $basename = class_basename($model);
        $confirm = $this->confirmField();

        $classContent = $this->replaceForAction(parent::buildClass($name), [$model, 'Heritage\Validation\Rule']);

        return str_replace(
            ['{{ model }}', '{{ variable }}', '{{ parameter }}', '{{ confirm }}', '{{ confirmLabel }}', '{{ label }}'],
            [$basename, Str::camel($basename), Str::snake($basename), $confirm, str_replace('_', ' ', $confirm), Str::snake($basename, ' ')],
            $classContent
        );
    }

    /**
     * The field typed to confirm the deletion of the --model, chosen as the generated delete
     * page chooses it (Blueprint::confirmField()): the slug, else the label field when it is
     * required and not translated, else the id.
     *
     * @return string
     */
    protected function confirmField(): string
    {
        $model = $this->modelClass();
        $instance = $this->modelInstance($model);
        $translatable = $this->modelTranslatable($model);
        $columns = $this->tableColumns($instance?->getTable());
        $types = $this->modelColumns($model);
        $fields = [];

        foreach ([...array_keys($types), ...$translatable] as $field) {
            $column = $columns[$field] ?? null;
            $string = $column === null
                ? ! str_ends_with($field, '_id') && ($types[$field] ?? 'string') === 'string'
                : in_array(strtolower($column['type_name']), ['varchar', 'char', 'character varying', 'nvarchar', 'string'], true);

            $fields[$field] = [
                'name' => $field,
                'kind' => $string ? 'string' : 'other',
                'rule' => '',
                'required' => ! ($column['nullable'] ?? false),
                'nullable' => (bool) ($column['nullable'] ?? false),
                'translatable' => in_array($field, $translatable, true),
                'related' => null,
            ];
        }

        return (new Blueprint((string) $this->option('artifact'), class_basename($model), $fields))->confirmField();
    }

    /**
     * The rules of --operation: the --model columns, typed by their casts, for store and
     * update; the `force` flag for a destroy request without a model.
     *
     * @return array<int, string> Rule lines of the rules() array.
     *
     * @throws InvalidArgumentException When the operation is unknown.
     */
    protected function operationRules(): array
    {
        $operation = $this->option('operation');

        if ($operation === null) {
            return [];
        }

        if (! in_array($operation, ['store', 'update', 'destroy'], true)) {
            throw new InvalidArgumentException("Unknown request operation [{$operation}]; use store, update or destroy.");
        }

        if ($operation === 'destroy') {
            return ["            'force' => 'sometimes|boolean',"];
        }

        $rules = [];
        $model = $this->modelClass();
        $instance = $this->modelInstance($model);
        $casts = $instance?->getCasts() ?? [];
        $nullable = array_keys(array_filter($this->tableColumns($instance?->getTable()), fn (array $column) => $column['nullable']));

        foreach ($this->modelColumns($model) as $column => $type) {
            $cast = strtolower((string) ($casts[$column] ?? ''));

            $rule = match (true) {
                str_ends_with($column, '_id') && ($table = $this->relatedTable($model, $column)) !== null => "required|integer|exists:{$table},id",
                str_contains($cast, 'date') => 'required|date',
                $type === 'int' => 'required|integer',
                $type === 'bool' => 'required|boolean',
                $type === 'float' => 'required|numeric',
                $type === 'array' => 'nullable|array',
                default => 'required|string|max:255',
            };

            if (in_array($column, $nullable, true)) {
                $rule = preg_replace('/^required\|/', 'nullable|', $rule);
            }

            $rules[] = '            '.$this->allowEmpty($column, "'{$column}' => '{$rule}',");
        }

        return $rules;
    }

    /**
     * The columns of the --model's translation table, by name, once it is migrated.
     *
     * @return array<string, array{type_name: string, nullable: bool}>
     */
    protected function translationColumns(): array
    {
        $instance = $this->modelInstance($this->modelClass());

        return $instance !== null && method_exists($instance, 'getTranslationTable')
            ? $this->tableColumns($instance->getTranslationTable())
            : [];
    }

    /**
     * The columns of a table of the --model's connection, by name, once it is migrated.
     *
     * @param  string|null  $table
     * @return array<string, array{type_name: string, nullable: bool}>
     */
    protected function tableColumns(?string $table): array
    {
        $instance = $this->modelInstance($this->modelClass());

        if ($instance === null || $table === null) {
            return [];
        }

        try {
            $schema = $instance->getConnection()->getSchemaBuilder();

            return $schema->hasTable($table) ? array_column($schema->getColumns($table), null, 'name') : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Make a rule line accept an empty value when its field is listed in --nullable.
     *
     * @param  string  $field
     * @param  string  $line  The rule line, as "'field' => 'required|...',".
     * @return string
     */
    protected function allowEmpty(string $field, string $line): string
    {
        $nullable = array_map('trim', explode(',', (string) $this->option('nullable')));

        return in_array($field, $nullable, true) ? str_replace("=> 'required|", "=> 'nullable|", $line) : $line;
    }

    /**
     * The table of the model a foreign key refers to: its belongsTo() relation's model, which may be
     * in another artifact, else the model of that name in the model's folder ("branch_id" names Branch).
     *
     * @param  string|null  $model
     * @param  string  $column
     * @return string|null Null when that model does not exist.
     */
    protected function relatedTable(?string $model, string $column): ?string
    {
        $instance = $this->modelInstance($model);
        $related = $instance !== null ? $this->relatedModelOf($instance, $column) : null;

        return $this->modelInstance($related)?->getTable();
    }

    /**
     * Fill the imports and the #[ForAction] attribute naming the action given by --action.
     *
     * @param  string  $stub
     * @param  array<int, string>  $imports  Further classes to import.
     * @return string
     */
    protected function replaceForAction(string $stub, array $imports = []): string
    {
        $imports = ['Heritage\Foundation\Http\FormRequest', ...$imports];
        $attribute = '';

        if ($action = $this->option('action')) {
            $action = $this->rootNamespace().str_replace('/', '\\', ltrim($action, '\\/'));
            $imports[] = 'Heritage\Foundation\Http\Attributes\ForAction';
            $imports[] = $action;
            $attribute = '#[ForAction('.class_basename($action)."::class)]\n";
        }

        sort($imports);

        return str_replace(
            ['{{ imports }}', '{{ forAction }}'],
            [implode('', array_map(fn ($import) => "use {$import};\n", $imports)), $attribute],
            $stub
        );
    }
}
