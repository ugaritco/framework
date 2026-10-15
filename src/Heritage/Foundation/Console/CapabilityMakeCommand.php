<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\GeneratesDomainActions;
use Heritage\Foundation\Console\Concerns\InspectsModel;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:capability')]
class CapabilityMakeCommand extends GeneratorCommand
{
    use GeneratesDomainActions, InspectsModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:capability
                    {name : The name of the capability}
                    {--artifact= : The target modular artifact name}
                    {--parcel= : The accepted Parcel (relative to Parcels/ or fully-qualified)}
                    {--nullable : Also accept no input (a nullable Parcel parameter)}
                    {--repository= : A repository contract to inject (relative to Contracts/Repositories/ or fully-qualified)}
                    {--result= : The Result enum (relative to Enums/ or fully-qualified)}
                    {--outcome= : The Outcome class (relative to Outcomes/ or fully-qualified)}
                    {--authorize= : The ability the capability requires (#[Authorize])}
                    {--model= : The model the ability is about (relative to Models/ or fully-qualified)}
                    {--operation= : Generate the body of an operation: index, create, show, edit, save or destroy}
                    {--f|force : Create the class even if the capability already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new capability class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Capability';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/capability.stub');
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

        if (! str_ends_with($name, 'Capability')) {
            $name .= 'Capability';
        }

        return parent::qualifyClass($name);
    }

    /**
     * Build the class, filling the Parcel type, injected repository and outcomes from the options.
     *
     * @param  string  $name  The fully-qualified class name.
     * @return string The generated source.
     */
    protected function buildClass($name)
    {
        $parcel = $this->companionClass('parcel', 'Parcels');
        $repository = $this->companionClass('repository', 'Contracts\\Repositories');
        $result = $this->companionClass('result', 'Enums');
        $outcome = $this->companionClass('outcome', 'Outcomes');

        $operation = $this->operation();
        $parcelClass = $parcel ?? 'Heritage\\Parcels\\Parcel';
        $nullable = $parcel === null || $this->option('nullable') || in_array($operation, ['index', 'create'], true);
        $parcelName = class_basename($parcelClass);
        $property = $repository ? $this->repositoryProperty($repository) : null;

        if ($operation !== null && $operation !== 'create' && $repository === null) {
            throw new InvalidArgumentException("The [{$operation}] operation reads its records through a repository; pass --repository.");
        }

        if ($repository) {
            $repositoryName = class_basename($repository);
            $constructor = <<<PHP
                /**
                 * @param  {$repositoryName}  \${$property}  Data access for this capability.
                 */
                public function __construct(protected {$repositoryName} \${$property})
                {
                    // Resolved by the container and promoted to \$this->{$property}.
                }
            PHP;
        } else {
            $constructor = <<<'PHP'
                /**
                 * Inject the repository contracts this capability needs, e.g.
                 * `protected BranchRepositoryInterface $branches`.
                 */
                public function __construct()
                {
                    // Promoted constructor parameters are resolved by the container.
                }
            PHP;
        }

        [$authorize, $authorizeImports] = $this->authorizeAttribute();

        $replacements = [
            '{{ imports }}' => $this->renderImports([
                'Heritage\\Capabilities\\Capability',
                'Heritage\\Outcomes\\Outcome',
                'Throwable',
                $parcelClass,
                $repository,
                $result,
                $outcome,
                ...$authorizeImports,
            ]),
            '{{ authorize }}' => $authorize,
            '{{ summary }}' => $this->summary($operation),
            '{{ body }}' => $this->body($operation, $result, $outcome, $property, $parcelName),
            '{{ constructor }}' => $constructor,
            '{{ parcelParameter }}' => $nullable ? "?{$parcelName} \$data = null" : "{$parcelName} \$data",
            '{{ parcelDoc }}' => $nullable ? "{$parcelName}|null" : $parcelName,
            '{{ success }}' => $this->outcomeExpression($result, $outcome, true),
            '{{ failure }}' => $this->outcomeExpression($result, $outcome, false),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), parent::buildClass($name));
    }

    /**
     * The --operation option, validated.
     *
     * @return string|null
     *
     * @throws InvalidArgumentException When the operation is unknown.
     */
    protected function operation(): ?string
    {
        $operation = $this->option('operation');

        if ($operation !== null && ! in_array($operation, ['index', 'create', 'show', 'edit', 'save', 'destroy'], true)) {
            throw new InvalidArgumentException("Unknown capability operation [{$operation}]; use index, create, show, edit, save or destroy.");
        }

        return $operation;
    }

    /**
     * The summary line of the capability's docblock.
     *
     * @param  string|null  $operation
     * @return string
     */
    protected function summary(?string $operation): string
    {
        $model = $this->modelLabel();
        $translations = $this->modelTranslatable($this->modelClass()) !== [] ? ' with its translations' : '';

        return match ($operation) {
            'index' => "Lists the {$model} records matching the criteria: filtered, searched and sorted, as one page or all.",
            'create' => "Prepares the {$model} creation form (no persistence).",
            'show' => "Loads a {$model}{$translations} for display.",
            'edit' => "Loads a {$model}{$translations} for its edit form.",
            'save' => "Creates a {$model}, or updates the attached one.",
            'destroy' => "Deletes a {$model} (permanently with `force`).",
            default => 'Encapsulates an atomic domain execution capability.',
        };
    }

    /**
     * The body of perform() for an operation, reading and writing through the repository.
     *
     * @param  string|null  $operation
     * @param  string|null  $result
     * @param  string|null  $outcome
     * @param  string|null  $property  The repository property.
     * @param  string  $parcel  The Parcel's class name.
     * @return string
     */
    protected function body(?string $operation, ?string $result, ?string $outcome, ?string $property, string $parcel): string
    {
        $success = fn (?string $data = null) => $this->outcomeExpression($result, $outcome, true, $data);
        $failure = $this->outcomeExpression($result, $outcome, false);
        $record = '$'.lcfirst($this->modelLabel());
        $loaded = $this->modelTranslatable($this->modelClass()) !== [] ? "{$record}->loadMissing(['translations'])" : $record;

        $lines = match ($operation) {
            'index' => ['return '.$success("\$this->{$property}->browse(\$data ?? new {$parcel})").';'],
            'create' => ['return '.$success().';'],
            'show', 'edit' => [
                "{$record} = \$data->getModel() ?? \$this->{$property}->find(\$data->id);",
                '',
                "if (! {$record}) {",
                "    return {$failure};",
                '}',
                '',
                'return '.$success($loaded).';',
            ],
            'save' => ['return '.$success("\$this->{$property}->save(\$data)").';'],
            'destroy' => [
                "return \$this->{$property}->destroy(\$data)",
                '    ? '.$success('$data->getModel()'),
                "    : {$failure};",
            ],
            default => ['//', '', 'return '.$success().';'],
        };

        return implode("\n", array_map(fn (string $line) => $line === '' ? '' : '        '.$line, $lines));
    }

    /**
     * The model's name for docblocks and variables: --model, or "record".
     *
     * @return string
     */
    protected function modelLabel(): string
    {
        $model = $this->option('model');

        return is_string($model) && $model !== '' ? class_basename(str_replace('/', '\\', $model)) : 'Record';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Capabilities';
    }
}
