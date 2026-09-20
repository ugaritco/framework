<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:feature')]
class FeatureMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:feature
                    {name : The name of the feature}
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
     * Add modular artifact options to the command definition.
     *
     * Features are strictly application-level workflows, so artifact options are omitted.
     *
     * @return void
     */
    protected function addArtifactOptions(): void
    {
        // Features reside strictly within app/Features
    }

    /**
     * Get the specified modular artifact name, if any.
     *
     * @return string|null
     */
    protected function getArtifactOption(): ?string
    {
        return null;
    }

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
