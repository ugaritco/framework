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
                    {artifact : The artifact the panel presents (e.g. library)}
                    {--icon= : The Lucide icon of the artifact (e.g. Library)}
                    {--group=master_data : The group of the main sidebar the artifact is listed in}
                    {--all : Also generate the pages of every model of the artifact with capabilities}
                    {--icons= : With --all, the Lucide icons of the models, as Model:Icon separated by commas (e.g. Book:BookOpen)}
                    {--stack= : The frontend stack (vue); detected from package.json by default}
                    {--f|force : Overwrite the files that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the panel of an artifact (layout, navigation, summary page, sidebar item, and the summary capability when missing), and with --all the pages of its models';

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
     * Execute the console command: the panel, then with --all make:pages for every model of the
     * artifact that has a store request (its capabilities), in the order their tables were created.
     *
     * @return int
     */
    public function handle(): int
    {
        $artifact = (string) $this->argument('artifact');

        if (! $this->files->isDirectory($this->ugarit->basePath("artifacts/{$artifact}"))) {
            $this->components->error("The artifact [{$artifact}] does not exist; create it with make:artifact.");

            return self::FAILURE;
        }

        if (! $stack = $this->stack()) {
            return self::FAILURE;
        }

        $icon = $this->iconName($this->option('icon'), 'Blocks');

        $this->writeFiles($this->artifactPath($artifact), $stack->panelFiles($artifact, $icon));
        $this->writeArtifactLang($artifact);
        $this->writeSummaryPage($stack, $artifact);
        $this->applyEdits($stack, $stack->panelRegistrations($artifact, $icon, (string) $this->option('group')));
        $this->printReport();
        $this->refreshFrontend();

        return $this->option('all') ? $this->makePages($artifact, $stack->name()) : self::SUCCESS;
    }

    /**
     * Run make:pages for every model of the artifact with capabilities.
     *
     * @param  string  $artifact
     * @param  string  $stack
     * @return int
     */
    protected function makePages(string $artifact, string $stack): int
    {
        $icons = $this->icons();
        $models = $this->models($artifact);

        if ($models === []) {
            $this->components->warn("The artifact [{$artifact}] has no model with capabilities yet; add one with make:model --all, then run make:pages.");
        }

        foreach ($models as $model) {
            $this->newLine();
            $this->components->info("Pages of [{$model}]");

            $status = $this->call('make:pages', [
                'model' => $model,
                '--artifact' => $artifact,
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
     * Write the artifact's lang/en/artifact.php, its name and description, as the sidebars and the
     * summary page show them.
     *
     * @param  string  $artifact
     * @return void
     */
    protected function writeArtifactLang(string $artifact): void
    {
        $manifest = $this->ugarit->basePath("artifacts/{$artifact}/art.php");
        $description = $this->files->exists($manifest) && preg_match("/\\\$description\\s*=\\s*'((?:[^'\\\\]|\\\\.)*)'/", $this->files->get($manifest), $match)
            ? $match[1]
            : '';

        $this->writeFile($this->ugarit->basePath("artifacts/{$artifact}/lang/en/artifact.php"), $this->renderStub('artifact.php', [
            '[[ title ]]' => Str::headline($artifact),
            '[[ description ]]' => $description,
        ]));
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
