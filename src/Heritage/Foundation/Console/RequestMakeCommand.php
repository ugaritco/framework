<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Ugarit\Prompts\confirm;
use function Ugarit\Prompts\select;
use function Ugarit\Prompts\text;

#[AsCommand(name: 'make:request')]
class RequestMakeCommand extends GeneratorCommand
{
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
        $classContent = $this->replaceForAction(parent::buildClass($name));

        if ($this->isTranslatable()) {
            $fieldsInput = (string) ($this->option('fields') ?: 'name');
            $fields = array_values(array_filter(array_map('trim', explode(',', $fieldsInput))));

            if (empty($fields)) {
                $fields = ['name'];
            }

            $fieldsArrayString = implode(', ', array_map(fn ($f) => "'{$f}'", $fields));

            $rulesLines = [];
            foreach ($fields as $field) {
                $rule = match ($field) {
                    'name', 'title' => "'{$field}' => 'required|string|min:2|max:64',",
                    'slug'          => "'{$field}' => 'required|string|min:2|max:64|unique:table,slug',",
                    'description', 'body', 'content', 'notes' => "'{$field}' => 'nullable|string',",
                    default         => "'{$field}' => 'required|string',",
                };
                $rulesLines[] = "            {$rule}";
            }

            $rulesString = implode("\n", $rulesLines);

            $classContent = str_replace(
                ['{{ translatable_fields }}', '{{translatable_fields}}', '{{ rules }}', '{{rules}}'],
                [$fieldsArrayString, $fieldsArrayString, $rulesString, $rulesString],
                $classContent
            );
        }

        return $classContent;
    }

    /**
     * Fill the imports and the #[ForAction] attribute naming the action given by --action.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceForAction(string $stub): string
    {
        $imports = ['Heritage\Foundation\Http\FormRequest'];
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
