<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\Command;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * ArtifactListCommand
 *
 * Discovers and displays a detailed list of all modular domain Artifacts/Modules
 * registered in the Ugarit application (`artifacts/`).
 */
#[AsCommand(name: 'artifact:list')]
class ArtifactListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artifact:list
                    {--json : Output the artifacts list as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered domain Artifacts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $artifacts = $this->discoverArtifacts();

        if ($this->option('json')) {
            $this->output->writeln(json_encode($artifacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        if (empty($artifacts)) {
            $this->components->warn('No domain artifacts found in [artifacts/].');
            $this->comment('You can scaffold your first artifact with: php scribe make:artifact');
            return 0;
        }

        $this->newLine();
        $this->components->info(sprintf('Discovered [%d] Modular Domain Artifact(s)', count($artifacts)));

        $rows = [];
        foreach ($artifacts as $art) {
            $caps = ! empty($art['capabilities'])
                ? implode(', ', array_slice(array_keys($art['capabilities']), 0, 3)) . (count($art['capabilities']) > 3 ? ' +' . (count($art['capabilities']) - 3) : '')
                : 'None';

            $rows[] = [
                $art['id'],
                $art['name'],
                $art['version'],
                $art['models_count'],
                $art['controllers_count'],
                $art['services_count'],
                $art['migrations_count'],
                $caps,
            ];
        }

        $this->table(
            ['Artifact ID', 'Name / Title', 'Version', 'Models', 'Controllers', 'Services', 'Migrations', 'Capabilities'],
            $rows
        );

        $this->newLine();
        return 0;
    }

    /**
     * Discover and inspect all artifacts inside the artifacts/ directory.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function discoverArtifacts(): array
    {
        $artifactsPath = $this->ugarit->basePath('artifacts');

        if (! is_dir($artifactsPath)) {
            return [];
        }

        $dirs = glob($artifactsPath . '/*', GLOB_ONLYDIR) ?: [];
        $artifacts = [];

        foreach ($dirs as $dir) {
            $slug = strtolower(basename($dir));
            $artFile = $dir . '/art.php';

            $name = Str::studly($slug) . ' Domain';
            $version = '1.00.00';
            $capabilities = [];

            if (file_exists($artFile)) {
                try {
                    $manifest = require $artFile;
                    if (is_object($manifest)) {
                        $name = $manifest->name ?? $name;
                        $version = $manifest->version ?? $version;
                        $capabilities = $manifest->capabilities ?? [];
                    }
                } catch (\Throwable $e) {
                    // Fallback to basic directory scanning on syntax error
                }
            }

            $modelsCount = count(glob($dir . '/src/Models/*.php') ?: []);
            $controllersCount = count(glob($dir . '/src/Http/Controllers/*.php') ?: []);
            $servicesCount = count(glob($dir . '/src/Services/*.php') ?: []);
            $migrationsCount = count(glob($dir . '/database/migrations/*.php') ?: []);

            $artifacts[] = [
                'id' => $slug,
                'name' => $name,
                'version' => $version,
                'path' => 'artifacts/' . $slug,
                'models_count' => $modelsCount,
                'controllers_count' => $controllersCount,
                'services_count' => $servicesCount,
                'migrations_count' => $migrationsCount,
                'capabilities' => $capabilities,
            ];
        }

        return $artifacts;
    }
}
