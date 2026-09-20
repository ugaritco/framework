<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\Command;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Ugarit\Prompts\confirm;
use function Ugarit\Prompts\text;

/**
 * ArtifactMakeCommand
 *
 * Scaffolds a complete modular Artifact directory structure inside `artifacts/{name}/`.
 *
 * Generated structure:
 *   artifacts/{name}/
 *   ├── art.php                              — Artifact manifest (id, name, version, providers, capabilities)
 *   ├── composer.json                        — Package definition (ugarit-artifacts/{name})
 *   ├── database/
 *   │   ├── factories/
 *   │   ├── migrations/
 *   │   └── seeders/
 *   └── src/
 *       ├── Providers/{Name}ServiceProvider.php
 *       ├── Models/
 *       ├── Http/Controllers/
 *       ├── Policies/
 *       ├── DTOs/
 *       ├── Services/
 *       ├── UseCases/
 *       ├── Features/
 *       └── Rules/
 */
#[AsCommand(name: 'make:artifact')]
class ArtifactMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:artifact
                    {name? : The slug name of the artifact (e.g. catalog, identity, blog)}
                    {--description= : A short human-readable description of the artifact}
                    {--minimal : Only scaffold the core files (art.php, composer.json, ServiceProvider) without subdirectories}
                    {--force : Overwrite the artifact even if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a complete modular Artifact domain with all required structure';

    /**
     * The directories created inside src/ when NOT using --minimal.
     *
     * @var array<int, string>
     */
    protected array $srcDirectories = [
        'Models',
        'Http/Controllers',
        'Policies',
        'DTOs',
        'Services',
        'UseCases',
        'Features',
        'Rules',
    ];

    /**
     * The database subdirectories to create.
     *
     * @var array<int, string>
     */
    protected array $databaseDirectories = [
        'factories',
        'migrations',
        'seeders',
    ];

    /**
     * Execute the console command.
     *
     * Resolves artifact name and description (prompting interactively when needed),
     * validates uniqueness, then scaffolds all directories and files.
     *
     * @return int Exit code — 0 for success, 1 for failure.
     */
    public function handle(): int
    {
        // --- Step 1: Resolve artifact name ---
        $name = $this->argument('name');

        if (! $name && $this->input->isInteractive()) {
            $name = text(
                label: 'What is the artifact name (slug)?',
                placeholder: 'e.g. catalog, identity, blog',
                required: true,
                validate: fn (string $v) => preg_match('/^[a-z][a-z0-9\-]*$/', $v)
                    ? null
                    : 'Name must be lowercase letters, numbers, and dashes only.',
            );
        }

        if (! $name) {
            $this->error('Artifact name is required.');
            return 1;
        }

        // Normalize slug to lowercase, trim whitespace
        $name = strtolower(trim($name));

        // --- Step 2: Resolve description ---
        $description = $this->option('description');

        if (! $description && $this->input->isInteractive()) {
            $description = text(
                label: 'Brief description of this artifact:',
                placeholder: 'e.g. Manages product catalog and categories',
                default: Str::studly($name) . ' domain artifact for Ugarit',
            );
        }

        // Fallback description if still empty
        $description = $description ?: Str::studly($name) . ' domain artifact for Ugarit';

        // --- Step 3: Compute derived values ---
        $studly      = Str::studly($name);           // e.g. "catalog" → "Catalog"
        $artifactPath = $this->ugarit->basePath("artifacts/{$name}");

        // --- Step 4: Guard against existing artifact ---
        if (is_dir($artifactPath) && ! $this->option('force')) {
            $this->components->error("Artifact [{$name}] already exists. Use --force to overwrite.");
            return 1;
        }

        // --- Step 5: Scaffold directory tree ---
        $this->scaffoldDirectories($artifactPath, $name);

        // --- Step 6: Write core files ---
        $this->writeArtFile($artifactPath, $name, $studly, $description);
        $this->writeComposerJson($artifactPath, $name, $studly, $description);
        $this->writeServiceProvider($artifactPath, $name, $studly);

        // --- Step 7: Output success summary ---
        $this->outputSummary($name, $studly, $artifactPath);

        return 0;
    }

    /**
     * Create the full directory tree for the artifact.
     *
     * When --minimal is used, only the src/Providers and database directories are created.
     * Otherwise the full src/ directory tree is scaffolded.
     *
     * @param  string  $artifactPath  Absolute path to the artifact root.
     * @param  string  $name          Slug name of the artifact.
     * @return void
     */
    protected function scaffoldDirectories(string $artifactPath, string $name): void
    {
        $isMinimal = $this->option('minimal');

        // Always create the Providers directory inside src
        $this->makeDirectory("{$artifactPath}/src/Providers");

        // Create database subdirectories
        foreach ($this->databaseDirectories as $dir) {
            $this->makeDirectory("{$artifactPath}/database/{$dir}");

            // Place a .gitkeep so empty directories are tracked by Git
            $gitkeep = "{$artifactPath}/database/{$dir}/.gitkeep";
            if (! file_exists($gitkeep)) {
                file_put_contents($gitkeep, '');
            }
        }

        // When not minimal, scaffold all src/ domain subdirectories
        if (! $isMinimal) {
            foreach ($this->srcDirectories as $dir) {
                $fullDir = "{$artifactPath}/src/{$dir}";
                $this->makeDirectory($fullDir);

                // Place a .gitkeep placeholder
                $gitkeep = "{$fullDir}/.gitkeep";
                if (! file_exists($gitkeep)) {
                    file_put_contents($gitkeep, '');
                }
            }
        }
    }

    /**
     * Write the `art.php` Artifact manifest file using the artifact.art.stub.
     *
     * @param  string  $artifactPath  Absolute path to the artifact root.
     * @param  string  $name          Slug name (e.g. "catalog").
     * @param  string  $studly        StudlyCase name (e.g. "Catalog").
     * @param  string  $description   Human-readable description.
     * @return void
     */
    protected function writeArtFile(string $artifactPath, string $name, string $studly, string $description): void
    {
        $stub = $this->getPopulatedStub('artifact.art', $name, $studly, $description);
        $path = "{$artifactPath}/art.php";

        if (! file_exists($path) || $this->option('force')) {
            file_put_contents($path, $stub);
        }
    }

    /**
     * Write the `composer.json` package definition using the artifact.composer.stub.
     *
     * @param  string  $artifactPath  Absolute path to the artifact root.
     * @param  string  $name          Slug name (e.g. "catalog").
     * @param  string  $studly        StudlyCase name (e.g. "Catalog").
     * @param  string  $description   Human-readable description.
     * @return void
     */
    protected function writeComposerJson(string $artifactPath, string $name, string $studly, string $description): void
    {
        $stub = $this->getPopulatedStub('artifact.composer', $name, $studly, $description);
        $path = "{$artifactPath}/composer.json";

        if (! file_exists($path) || $this->option('force')) {
            file_put_contents($path, $stub);
        }
    }

    /**
     * Write the `{Name}ServiceProvider.php` class using the artifact.provider.stub.
     *
     * @param  string  $artifactPath  Absolute path to the artifact root.
     * @param  string  $name          Slug name (e.g. "catalog").
     * @param  string  $studly        StudlyCase name (e.g. "Catalog").
     * @return void
     */
    protected function writeServiceProvider(string $artifactPath, string $name, string $studly): void
    {
        $stub = $this->getPopulatedStub('artifact.provider', $name, $studly);
        $path = "{$artifactPath}/src/Providers/{$studly}ServiceProvider.php";

        if (! file_exists($path) || $this->option('force')) {
            file_put_contents($path, $stub);
        }
    }

    /**
     * Load a stub file and replace all placeholder tokens with real values.
     *
     * Tokens replaced:
     *   {{ name }}        — lowercase slug (e.g. "catalog")
     *   {{ StudlyName }}  — StudlyCase name (e.g. "Catalog")
     *   {{ description }} — human-readable description string
     *
     * @param  string       $stubName    Stub filename without ".stub" extension.
     * @param  string       $name        Slug name.
     * @param  string       $studly      StudlyCase name.
     * @param  string|null  $description Optional description string.
     * @return string                    Populated stub content ready to write.
     */
    protected function getPopulatedStub(string $stubName, string $name, string $studly, ?string $description = null): string
    {
        // Resolve the stub path — allow application-level overrides first
        $stubPath = file_exists($customPath = $this->ugarit->basePath("stubs/{$stubName}.stub"))
            ? $customPath
            : __DIR__ . "/stubs/{$stubName}.stub";

        $content = file_get_contents($stubPath);

        // Replace all stub placeholders with concrete values
        return str_replace(
            ['{{ name }}', '{{ StudlyName }}', '{{ description }}'],
            [$name,        $studly,             $description ?? ''],
            $content
        );
    }

    /**
     * Recursively create a directory with correct permissions if it does not exist.
     *
     * @param  string  $path  The absolute directory path to create.
     * @return void
     */
    protected function makeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Output a rich, formatted summary of everything that was scaffolded.
     *
     * Lists all created files and directories, and provides next-step hints
     * so the developer knows what to do after the artifact is generated.
     *
     * @param  string  $name          Slug name of the artifact.
     * @param  string  $studly        StudlyCase name.
     * @param  string  $artifactPath  Absolute path to the artifact root.
     * @return void
     */
    protected function outputSummary(string $name, string $studly, string $artifactPath): void
    {
        $isMinimal = $this->option('minimal');

        $this->newLine();
        $this->components->info("Artifact [{$studly}] scaffolded successfully.");
        $this->newLine();

        // Show the core files created
        $this->components->twoColumnDetail(
            '<fg=green>art.php</>',
            "artifacts/{$name}/art.php"
        );
        $this->components->twoColumnDetail(
            '<fg=green>composer.json</>',
            "artifacts/{$name}/composer.json"
        );
        $this->components->twoColumnDetail(
            '<fg=green>' . $studly . 'ServiceProvider</>',
            "artifacts/{$name}/src/Providers/{$studly}ServiceProvider.php"
        );

        if (! $isMinimal) {
            $this->newLine();
            $this->components->twoColumnDetail('<fg=yellow>Directories created</>', '');
            foreach (['Models', 'Http/Controllers', 'Policies', 'DTOs', 'Services', 'UseCases', 'Features', 'Rules'] as $dir) {
                $this->components->twoColumnDetail(
                    "  <fg=gray>src/{$dir}</>",
                    "artifacts/{$name}/src/{$dir}"
                );
            }
        }

        $this->newLine();

        // Display next-step hints for the developer
        $this->components->warn('Next steps:');
        $this->line("  1. Register the artifact in your application's <fg=cyan>composer.json</> path repositories.");
        $this->line("  2. Run <fg=cyan>composer require ugarit-artifacts/{$name}:@dev</> to load it.");
        $this->line("  3. Start adding models: <fg=cyan>php scribe make:model Product --artifact={$name}</>");

        $this->newLine();
    }
}
