<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\Command;
use Heritage\Database\Eloquent\Concerns\HasTranslation;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\ModelTranslation;
use Heritage\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * ModelListCommand
 *
 * Discovers and displays a comprehensive list of all Eloquent models
 * residing in the application root (`app/Models`) and modular domain Artifacts (`artifacts/*`).
 */
#[AsCommand(name: 'model:list')]
class ModelListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model:list
                    {--artifact= : Filter models by modular domain artifact}
                    {--translatable : Only display translatable multilingual models}
                    {--json : Output the models list as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Eloquent models across the application and modular domain artifacts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $models = $this->discoverModels();

        // Apply filters
        $artifactFilter = $this->option('artifact');
        if ($artifactFilter) {
            $artifactFilter = strtolower($artifactFilter);
            $models = array_values(array_filter($models, fn ($m) => strtolower($m['artifact_slug'] ?? '') === $artifactFilter));
        }

        if ($this->option('translatable')) {
            $models = array_values(array_filter($models, fn ($m) => $m['is_translatable'] || $m['is_translation_model']));
        }

        if ($this->option('json')) {
            $this->output->writeln(json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        if (empty($models)) {
            $this->components->warn('No Eloquent models found matching the specified criteria.');
            $this->comment('You can scaffold a new model with: php scribe make:model [Name]');
            return 0;
        }

        $this->newLine();
        $this->components->info(sprintf('Discovered [%d] Eloquent Model(s)', count($models)));

        $rows = [];
        foreach ($models as $m) {
            $typeLabel = $m['is_translation_model']
                ? '<fg=cyan>Translation Model</>'
                : ($m['is_translatable'] ? '<fg=green>Translatable (Dual Table)</>' : 'Standard');

            $rows[] = [
                $m['class'],
                $m['location'],
                $m['table'],
                $typeLabel,
                $m['fillable_count'] > 0 ? (string) $m['fillable_count'] : '-',
                $m['file_path'],
            ];
        }

        $this->table(
            ['Model Class', 'Location', 'Database Table', 'Type', 'Fillable Fields', 'File Path'],
            $rows
        );

        $this->newLine();
        return 0;
    }

    /**
     * Discover all Eloquent models in app/Models and artifacts/*.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function discoverModels(): array
    {
        $models = [];

        // 1. Discover Application Root Models (app/Models)
        $appModelsDir = $this->ugarit->path('Models');
        if (is_dir($appModelsDir)) {
            $files = glob($appModelsDir . '/*.php') ?: [];
            foreach ($files as $file) {
                $base = basename($file, '.php');
                $class = "App\\Models\\{$base}";
                $inspected = $this->inspectModel($class, $file, 'App', null);
                if ($inspected) {
                    $models[] = $inspected;
                }
            }
        }

        // 2. Discover Domain Artifact Models (artifacts/*/src/Models)
        $artifactsPath = $this->ugarit->basePath('artifacts');
        if (is_dir($artifactsPath)) {
            $artifactDirs = glob($artifactsPath . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($artifactDirs as $artDir) {
                $artSlug = strtolower(basename($artDir));
                $artStudly = Str::studly($artSlug);
                $modelsDir = $artDir . '/src/Models';

                if (is_dir($modelsDir)) {
                    $files = glob($modelsDir . '/*.php') ?: [];
                    foreach ($files as $file) {
                        $base = basename($file, '.php');
                        $class = "Ugarit\\Artifacts\\{$artStudly}\\Models\\{$base}";
                        $inspected = $this->inspectModel($class, $file, "Artifact: {$artStudly}", $artSlug);
                        if ($inspected) {
                            $models[] = $inspected;
                        }
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Inspect a specific model class and extract its architectural metadata.
     *
     * @param  string       $class
     * @param  string       $filePath
     * @param  string       $location
     * @param  string|null  $artifactSlug
     * @return array<string, mixed>|null
     */
    protected function inspectModel(string $class, string $filePath, string $location, ?string $artifactSlug): ?array
    {
        $relativePath = Str::after($filePath, $this->ugarit->basePath() . DIRECTORY_SEPARATOR);

        // Compute fallback table name using standard convention
        $baseName = class_basename($class);
        $computedTable = Str::snake(Str::pluralStudly($baseName));
        $isTranslatable = false;
        $isTranslationModel = false;
        $fillableCount = 0;

        // Inspect file content directly for rapid headless discovery
        $content = file_get_contents($filePath);
        if ($content !== false) {
            if (str_contains($content, 'Translatable') || str_contains($content, 'HasTranslation') || str_contains($content, '$translatable')) {
                $isTranslatable = true;
            }
            if (str_contains($content, 'ModelTranslation') || str_ends_with($baseName, 'Translation')) {
                $isTranslationModel = true;
            }
            if (preg_match('/protected\s+(?:array\s+)?\$table\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                $computedTable = $matches[1];
            }
            if (preg_match('/protected\s+(?:array\s+)?\$fillable\s*=\s*\[(.*?)\]/s', $content, $matches)) {
                $items = array_filter(array_map('trim', explode(',', $matches[1])));
                $fillableCount = count(array_filter($items, fn ($i) => ! empty($i) && ! str_starts_with($i, '//')));
            }
        }

        return [
            'class' => $class,
            'name' => $baseName,
            'location' => $location,
            'artifact_slug' => $artifactSlug,
            'table' => $computedTable,
            'is_translatable' => $isTranslatable,
            'is_translation_model' => $isTranslationModel,
            'fillable_count' => $fillableCount,
            'file_path' => $relativePath,
        ];
    }
}
