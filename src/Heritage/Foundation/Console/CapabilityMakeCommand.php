<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\GeneratesDomainActions;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:capability')]
class CapabilityMakeCommand extends GeneratorCommand
{
    use GeneratesDomainActions;

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

        $parcelClass = $parcel ?? 'Heritage\\Parcels\\Parcel';
        $nullable = $parcel === null || $this->option('nullable');
        $parcelName = class_basename($parcelClass);

        if ($repository) {
            $repositoryName = class_basename($repository);
            $property = $this->repositoryProperty($repository);
            $constructor = <<<PHP
                /**
                 * @param  {$repositoryName}  \${$property}  Data access for this capability.
                 */
                public function __construct(protected {$repositoryName} \${$property})
                {
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
            '{{ constructor }}' => $constructor,
            '{{ parcelParameter }}' => $nullable ? "?{$parcelName} \$data = null" : "{$parcelName} \$data",
            '{{ parcelDoc }}' => $nullable ? "{$parcelName}|null" : $parcelName,
            '{{ success }}' => $this->outcomeExpression($result, $outcome, true),
            '{{ failure }}' => $this->outcomeExpression($result, $outcome, false),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), parent::buildClass($name));
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
