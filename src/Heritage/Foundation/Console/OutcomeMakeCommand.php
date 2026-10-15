<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Heritage\Foundation\Console\Concerns\GeneratesDomainActions;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Generates the Outcome of a model's capabilities under Outcomes/{Model}: the placeholders of its
 * feedback messages and, given the save result, the action offering to view a saved record.
 */
#[AsCommand(name: 'make:outcome')]
class OutcomeMakeCommand extends GeneratorCommand
{
    use GeneratesDomainActions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:outcome
                    {name : The model the outcome serves (e.g. Department), or a path such as Department/DepartmentOutcome}
                    {--artifact= : The target modular artifact name}
                    {--model= : The model class when it differs from the name (relative to Models/ or fully-qualified)}
                    {--result= : The save Result enum whose success offers to view the record (relative to Enums/ or fully-qualified)}
                    {--route= : The route name base (defaults to the kebab-cased model)}
                    {--f|force : Create the class even if the outcome already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new outcome class for a model\'s capabilities';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Outcome';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/outcome.stub');
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
     * A bare model name becomes Outcomes\{Model}\{Model}Outcome; a path is kept.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = str_replace('/', '\\', ltrim($name, '\\/'));
        $base = preg_replace('/Outcome$/', '', $name);

        if (! str_contains($name, '\\') && ! str_starts_with($name, $this->rootNamespace())) {
            $name = "{$base}\\{$base}";
        }

        return parent::qualifyClass(str_ends_with($name, 'Outcome') ? $name : $name.'Outcome');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Outcomes';
    }

    /**
     * Build the outcome, filling the model, its lang group, route base and view action.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $model = $this->outcomeModel($name);
        $modelName = class_basename($model);
        $result = $this->companionClass('result', 'Enums');
        $route = $this->option('route') ?: Str::kebab($modelName);

        $replacements = [
            '{{ imports }}' => $this->renderImports([
                'Heritage\\Outcomes\\Outcome',
                $model,
                $result ? 'Heritage\\Actions\\Action' : null,
                $result,
            ]),
            '{{ model }}' => $modelName,
            '{{ lang }}' => $this->langGroup($modelName),
            '{{ route }}' => $route,
            '{{ actions }}' => $result ? $this->viewAction($modelName, class_basename($result), $route) : '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), parent::buildClass($name));
    }

    /**
     * The model class: --model, or the outcome's name without "Outcome" under Models/.
     *
     * @param  string  $name  The outcome class.
     * @return string
     */
    protected function outcomeModel(string $name): string
    {
        $model = $this->option('model') ?: preg_replace('/Outcome$/', '', class_basename($name));
        $model = str_replace('/', '\\', trim((string) $model, '\\/'));

        return class_exists($model) ? $model : $this->qualifyModel($model);
    }

    /**
     * The translation group of the model: "{artifact}::{model}" in an artifact, "{model}" otherwise.
     *
     * @param  string  $model
     * @return string
     */
    protected function langGroup(string $model): string
    {
        $artifact = $this->getArtifactOption();

        return ($artifact ? Str::kebab($artifact).'::' : '').Str::snake($model);
    }

    /**
     * The actions() method offering to view a record once it is saved.
     *
     * @param  string  $model
     * @param  string  $result
     * @param  string  $route
     * @return string
     */
    protected function viewAction(string $model, string $result, string $route): string
    {
        return <<<PHP

            /**
             * Offer to view the record once it is saved.
             *
             * @return array<int, Action>
             */
            public function actions(): array
            {
                \$subject = \$this->subject();

                if (\$this->result !== {$result}::SUCCESS || ! \$subject instanceof {$model}) {
                    return parent::actions();
                }

                return [
                    ...parent::actions(),
                    Action::redirect(route('{$route}.show', \$subject), __('actions.view')),
                ];
            }

        PHP;
    }
}
