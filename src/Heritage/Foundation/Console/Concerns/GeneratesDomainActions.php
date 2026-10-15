<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Concerns;

use Heritage\Support\Str;

/**
 * Shared scaffolding for the Capability and Feature generators.
 *
 * Resolves the companion classes given as options (--parcel, --result, --outcome, --repository,
 * --model) relative to the artifact's conventional folders, and renders the outcome expressions,
 * the #[Authorize] attribute and the import block of the generated action.
 */
trait GeneratesDomainActions
{
    /**
     * Resolve a companion class option to a fully-qualified class name.
     *
     * An existing fully-qualified class is used as-is; anything else is placed under the given
     * conventional folder of the root namespace (e.g. "Branch/ShowBranchParcel" with folder
     * "Parcels" becomes "{Root}\Parcels\Branch\ShowBranchParcel").
     *
     * @param  string  $option  The command option holding the class.
     * @param  string  $folder  The conventional folder below the root namespace.
     * @return string|null The resolved class, or null when the option is empty.
     */
    protected function companionClass(string $option, string $folder): ?string
    {
        $value = $this->option($option);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = str_replace('/', '\\', trim($value, '\\/'));

        if (class_exists($value) || interface_exists($value) || enum_exists($value)) {
            return $value;
        }

        $rootNamespace = trim($this->rootNamespace(), '\\');

        if (str_starts_with($value, $rootNamespace.'\\')) {
            return $value;
        }

        $class = $rootNamespace.'\\'.$folder.'\\'.$value;

        if (! class_exists($class) && ! interface_exists($class) && ! enum_exists($class)) {
            $this->components->warn("[{$class}] does not exist yet; create it before running the generated class.");
        }

        return $class;
    }

    /**
     * Render the expression building an outcome for the given result case.
     *
     * @param  string|null  $result  The result enum class, if any.
     * @param  string|null  $outcome  The outcome class, if any.
     * @param  bool  $success  Whether the expression represents success (SUCCESS) or failure (FAILED).
     * @param  string|null  $data  The PHP expression of the data a success carries, if any.
     * @return string The PHP expression, without a trailing semicolon.
     */
    protected function outcomeExpression(?string $result, ?string $outcome, bool $success, ?string $data = null): string
    {
        $case = $success ? 'SUCCESS' : 'FAILED';
        $data = $success && $data !== null ? $data : null;

        return match (true) {
            $result !== null => ($outcome ? class_basename($outcome) : 'Outcome').'::fromResult('.class_basename($result)."::{$case}".($data ? ", {$data}" : '').')',
            $success => ($outcome ? class_basename($outcome) : 'Outcome')."::success({$data})",
            default => ($outcome ? class_basename($outcome) : 'Outcome').'::failure()',
        };
    }

    /**
     * Render the #[Authorize] attribute from --authorize and --model.
     *
     * @return array{0: string, 1: array<int, string|null>} The attribute line (empty without
     *                                                      --authorize) and the classes it imports.
     */
    protected function authorizeAttribute(): array
    {
        $ability = $this->option('authorize');

        if (! is_string($ability) || trim($ability) === '') {
            return ['', []];
        }

        $model = $this->companionClass('model', 'Models');
        $arguments = var_export(trim($ability), true).($model ? ', '.class_basename($model).'::class' : '');

        return ["#[Authorize({$arguments})]\n", ['Heritage\\Capabilities\\Attributes\\Authorize', $model]];
    }

    /**
     * Name the constructor property holding a repository contract.
     *
     * BranchRepositoryInterface becomes "branches".
     *
     * @param  string  $repository  The repository contract class.
     * @return string The property name.
     */
    protected function repositoryProperty(string $repository): string
    {
        $base = preg_replace('/(Repository)?(Interface|Contract)?$/', '', class_basename($repository));

        return Str::camel(Str::plural($base !== '' ? $base : 'records'));
    }

    /**
     * Render a sorted, de-duplicated import block.
     *
     * @param  array<int, string|null>  $classes  The classes to import.
     * @return string The "use" statements, one per line.
     */
    protected function renderImports(array $classes): string
    {
        $classes = array_unique(array_filter($classes));
        sort($classes);

        return implode("\n", array_map(fn (string $class) => "use {$class};", $classes));
    }
}
