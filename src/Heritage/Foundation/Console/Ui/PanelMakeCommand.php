<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui;

use Heritage\Console\Command;
use Heritage\Filesystem\Filesystem;
use Heritage\Foundation\Console\Ui\Concerns\GeneratesUi;
use Heritage\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:panel')]
class PanelMakeCommand extends Command
{
    use GeneratesUi;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:panel
                    {artifact : The artifact the panel module presents (e.g. library)}
                    {--icon= : The Lucide icon of the module (e.g. Library)}
                    {--group=master_data : The group of the main sidebar the module is listed in}
                    {--all : Also generate the pages of every model of the artifact with capabilities}
                    {--icons= : With --all, the Lucide icons of the models, as Model:Icon separated by commas (e.g. Book:BookOpen)}
                    {--stack= : The frontend stack (vue); detected from package.json by default}
                    {--f|force : Overwrite the files that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the panel module of an artifact (layout, navigation, summary page and route, sidebar item), and with --all the pages of its models';

    /**
     * Create a new command instance.
     *
     * @param  Filesystem  $files
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command: the module, then with --all make:pages for every model of the
     * artifact that has a store request (its capabilities), in the order their tables were created.
     *
     * @return int
     */
    public function handle(): int
    {
        $module = (string) $this->argument('artifact');

        if (! $this->files->isDirectory($this->ugarit->basePath("artifacts/{$module}"))) {
            $this->components->error("The artifact [{$module}] does not exist; create it with make:artifact.");

            return self::FAILURE;
        }

        if (! $stack = $this->stack()) {
            return self::FAILURE;
        }

        $icon = $this->iconName($this->option('icon'), 'Blocks');

        $this->writeFiles($this->modulePath($module), $stack->panelFiles($module, $icon));
        $this->writeModuleLang($module);
        $this->addSummaryRoute($module);
        $this->applyEdits($stack, $stack->panelRegistrations($module, $icon, (string) $this->option('group')));
        $this->printReport();
        $this->refreshFrontend();

        return $this->option('all') ? $this->makePages($module, $stack->name()) : self::SUCCESS;
    }

    /**
     * Run make:pages for every model of the artifact with capabilities.
     *
     * @param  string  $module
     * @param  string  $stack
     * @return int
     */
    protected function makePages(string $module, string $stack): int
    {
        $icons = $this->icons();
        $models = $this->models($module);

        if ($models === []) {
            $this->components->warn("The artifact [{$module}] has no model with capabilities yet; add one with make:model --all, then run make:pages.");
        }

        foreach ($models as $model) {
            $this->newLine();
            $this->components->info("Pages of [{$model}]");

            $status = $this->call('make:pages', [
                'model' => $model,
                '--artifact' => $module,
                '--icon' => $icons[$model] ?? null,
                '--stack' => $stack,
                '--force' => (bool) $this->option('force'),
            ]);

            if ($status !== self::SUCCESS) {
                return $status;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Write the artifact's lang/en/module.php, the module's name and the artifact's description, as
     * the sidebars and the summary page show them.
     *
     * @param  string  $module
     * @return void
     */
    protected function writeModuleLang(string $module): void
    {
        $manifest = $this->ugarit->basePath("artifacts/{$module}/art.php");
        $description = $this->files->exists($manifest) && preg_match("/\\\$description\\s*=\\s*'((?:[^'\\\\]|\\\\.)*)'/", $this->files->get($manifest), $match)
            ? $match[1]
            : '';

        $this->writeFile($this->ugarit->basePath("artifacts/{$module}/lang/en/module.php"), $this->renderStub('module.php', [
            '[[ title ]]' => Str::headline($module),
            '[[ description ]]' => $description,
        ]));
    }

    /**
     * Add the summary page's route to the artifact's routes/web.php: first in its group, or in a new
     * file grouping the artifact's routes under panel/artifact/{artifact}.
     *
     * @param  string  $module
     * @return void
     */
    protected function addSummaryRoute(string $module): void
    {
        $path = $this->ugarit->basePath("artifacts/{$module}/routes/web.php");
        $route = "Route::inertia('/', '{$module}::module/Show')->name('{$module}.module.show');";

        if (! $this->files->exists($path)) {
            $this->writeFile($path, "<?php\n\nuse Heritage\\Support\\Facades\\Route;\n\nRoute::middleware(['web', 'auth'])->prefix('panel/artifact/{$module}')->group(function () {\n    {$route}\n});\n");

            return;
        }

        $this->editFile($path, function (string $source) use ($path, $route, $module) {
            if (str_contains($source, "'{$module}.module.show'")) {
                return $source;
            }

            if (! preg_match('/->group\(function \(\) \{\r?\n/', $source, $match, PREG_OFFSET_CAPTURE)) {
                $this->misses[] = "{$path}: the routes group ({$route})";

                return $source;
            }

            $eol = str_contains($source, "\r\n") ? "\r\n" : "\n";

            return substr_replace($source, "    {$route}{$eol}{$eol}", $match[0][1] + strlen($match[0][0]), 0);
        });
    }

    /**
     * The icons given by --icons, by model.
     *
     * @return array<string, string>
     */
    protected function icons(): array
    {
        $icons = [];

        foreach (array_filter(explode(',', (string) $this->option('icons'))) as $pair) {
            [$model, $icon] = array_pad(explode(':', trim($pair), 2), 2, '');

            if ($icon !== '') {
                $icons[Str::studly($model)] = $icon;
            }
        }

        return $icons;
    }

    /**
     * The models of the artifact that have a store request, ordered by the migration creating their
     * table (models without one come last, by name).
     *
     * @param  string  $artifact
     * @return array<int, string>
     */
    protected function models(string $artifact): array
    {
        $base = $this->ugarit->basePath("artifacts/{$artifact}");
        $migrations = $this->files->isDirectory("{$base}/database/migrations")
            ? array_map(fn ($file) => $file->getFilename(), $this->files->files("{$base}/database/migrations"))
            : [];
        sort($migrations);
        $models = [];

        foreach ($this->files->isDirectory("{$base}/src/Models") ? $this->files->files("{$base}/src/Models") : [] as $file) {
            $model = $file->getFilenameWithoutExtension();

            if (! $this->files->exists("{$base}/src/Http/Requests/{$model}/Capabilities/Store{$model}Request.php")) {
                continue;
            }

            $create = '_create_'.Str::snake(Str::pluralStudly($model)).'_table.php';
            $position = array_key_first(array_filter($migrations, fn (string $name) => str_ends_with($name, $create)));
            $models[$model] = $position ?? PHP_INT_MAX;
        }

        uksort($models, fn (string $a, string $b) => [$models[$a], $a] <=> [$models[$b], $b]);

        return array_keys($models);
    }
}
