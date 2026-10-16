<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\GeneratesActionSlice;
use Heritage\Foundation\Console\Concerns\GeneratesDomainActions;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:feature')]
class FeatureMakeCommand extends GeneratorCommand
{
    use GeneratesActionSlice, GeneratesDomainActions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:feature
                    {name : The name of the feature}
                    {--artifact= : The target modular artifact name}
                    {--parcel= : The accepted Parcel (relative to Parcels/ or fully-qualified)}
                    {--result= : The Result enum (relative to Enums/ or fully-qualified)}
                    {--outcome= : The Outcome class (relative to Outcomes/ or fully-qualified)}
                    {--authorize= : The ability the feature requires (#[Authorize])}
                    {--model= : The model the ability is about (relative to Models/ or fully-qualified)}
                    {--a|all : Also generate its result, parcel, form request, controller method, route, feedback messages and component}
                    {--fields= : The typed input of --all (e.g. code:string,note:?string): the parcel properties and the request rules}
                    {--feedback= : The feedback key of the --all result (its snake name by default, e.g. register_member)}
                    {--route= : The --all route as verb:uri (e.g. post:members/register); POST on its kebab name by default}
                    {--route-name= : The name of the --all route (its dotted kebab name by default)}
                    {--controller= : The controller --all adds the method to (relative to Http/Controllers; its folder\'s controller by default)}
                    {--method= : The controller method of --all (its camel name by default)}
                    {--page= : The page the --all GET method renders (e.g. company::members/Register); JSON without it}
                    {--resource= : The resource presenting the outcome data in the --all method (relative to Http/Resources or fully-qualified)}
                    {--f|force : Create the class even if the feature already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new feature class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Feature';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/feature.stub');
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

        if (! str_ends_with($name, 'Feature')) {
            $name .= 'Feature';
        }

        return parent::qualifyClass($name);
    }

    /**
     * Build the class, filling the accepted Parcel and the outcomes from the options.
     *
     * @param  string  $name  The fully-qualified class name.
     * @return string The generated source.
     */
    protected function buildClass($name)
    {
        $parcel = $this->companionClass('parcel', 'Parcels');
        $result = $this->companionClass('result', 'Enums');
        $outcome = $this->companionClass('outcome', 'Outcomes');

        [$authorize, $authorizeImports] = $this->authorizeAttribute();

        $replacements = [
            '{{ imports }}' => $this->renderImports([
                'Heritage\\Features\\Feature',
                'Heritage\\Features\\Flow\\FlowState',
                'Heritage\\Outcomes\\Outcome',
                $parcel ?? 'Heritage\\Parcels\\Parcel',
                $result,
                $outcome,
                ...$authorizeImports,
            ]),
            '{{ authorize }}' => $authorize,
            '{{ parcel }}' => class_basename($parcel ?? 'Heritage\\Parcels\\Parcel'),
            '{{ flow }}' => $this->flowArguments($result, $outcome),
            '{{ success }}' => $this->outcomeExpression($result, $outcome, true),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), parent::buildClass($name));
    }

    /**
     * Render the arguments of flow(): the feature's Outcome class and its FAILED result.
     *
     * @param  string|null  $result  The Result enum.
     * @param  string|null  $outcome  The Outcome class.
     * @return string
     */
    protected function flowArguments(?string $result, ?string $outcome): string
    {
        $failWith = $result ? class_basename($result).'::FAILED' : null;

        return match (true) {
            $outcome && $failWith => class_basename($outcome)."::class, {$failWith}",
            (bool) $outcome => class_basename($outcome).'::class',
            (bool) $failWith => "failWith: {$failWith}",
            default => '',
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function sliceLayer(): string
    {
        return 'Features';
    }

    /**
     * {@inheritdoc}
     */
    protected function sliceAcceptsNoInput(): bool
    {
        return false;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Features';
    }
}
