<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui\Concerns;

use Closure;
use Heritage\Foundation\Console\Concerns\EditsSource;
use Heritage\Foundation\Console\Ui\Stacks\Stack;
use Heritage\Foundation\Console\Ui\Stacks\VueStack;
use Heritage\Support\Str;
use Throwable;

/**
 * Writes and edits the files of the panel frontend (resources/js/apps/panel) for the frontend
 * generators, in the stack of the application: files rendered from the stack's stubs, edits of
 * existing files reported as updated, unchanged or missing, then the Wayfinder routes and the
 * translations the new pages rely on.
 *
 * The using command provides $files (a Filesystem), and the --stack and --force options.
 */
trait GeneratesUi
{
    use EditsSource;

    /**
     * The stacks the panel can be generated in, by name.
     *
     * @var array<string, class-string<Stack>>
     */
    protected static array $stacks = [
        'vue' => VueStack::class,
    ];

    /**
     * The written and edited files, with their status.
     *
     * @var array<string, string>
     */
    protected array $report = [];

    /**
     * The places that could not be found, as "path: place".
     *
     * @var array<int, string>
     */
    protected array $misses = [];

    /**
     * The stack of the application: given by --stack, else by the ui.stack config, else detected
     * from the dependencies of its package.json; null when none applies.
     *
     * @return Stack|null
     */
    protected function detectStack(): ?Stack
    {
        $name = $this->option('stack') ?: config('ui.stack');

        if ($name) {
            return isset(static::$stacks[$name]) ? new (static::$stacks[$name]) : null;
        }

        $package = $this->ugarit->basePath('package.json');
        $json = $this->files->exists($package) ? (array) json_decode($this->files->get($package), true) : [];
        $dependencies = [...($json['dependencies'] ?? []), ...($json['devDependencies'] ?? [])];

        foreach (static::$stacks as $class) {
            $stack = new $class;

            if ($stack->detects($dependencies)) {
                return $stack;
            }
        }

        return null;
    }

    /**
     * The stack of the application, or an error naming the supported stacks.
     *
     * @return Stack|null
     */
    protected function stack(): ?Stack
    {
        $stack = $this->detectStack();

        if ($stack === null) {
            $given = $this->option('stack') ?: config('ui.stack');
            $this->components->error(($given ? "The stack [{$given}] is not supported" : 'No supported frontend stack was found in package.json')
                .'; the supported stacks are: '.implode(', ', array_keys(static::$stacks)).' (choose one with --stack).');
        }

        return $stack;
    }

    /**
     * The path of the panel application, or of a file in it.
     *
     * @param  string  $path
     * @return string
     */
    protected function panelPath(string $path = ''): string
    {
        return $this->ugarit->basePath('resources/js/apps/panel'.($path === '' ? '' : '/'.$path));
    }

    /**
     * The path of a panel module, or of a file in it.
     *
     * @param  string  $module
     * @param  string  $path
     * @return string
     */
    protected function modulePath(string $module, string $path = ''): string
    {
        return $this->panelPath("modules/{$module}".($path === '' ? '' : '/'.$path));
    }

    /**
     * Render a UI stub; the application's stubs/ui folder overrides the framework's. A placeholder
     * named "@flag" keeps (true) or drops (false) the "[[ @flag ]] … [[ @end ]]" blocks of the stub,
     * "[[ @a|b ]]" being kept when either flag is true.
     *
     * @param  string  $name  The stub path under stubs/ui, without ".stub".
     * @param  array<string, string|bool>  $replace  Placeholder => value.
     * @return string
     */
    protected function renderStub(string $name, array $replace): string
    {
        $custom = $this->ugarit->basePath("stubs/ui/{$name}.stub");
        $stub = $this->files->get($this->files->exists($custom) ? $custom : __DIR__."/../stubs/{$name}.stub");
        $flags = array_filter($replace, fn ($value, string $key) => str_starts_with($key, '@'), ARRAY_FILTER_USE_BOTH);
        $block = '/^[ \t]*\[\[ @(?!end\b)([\w|]+) \]\]\r?\n((?:(?![ \t]*\[\[ @).*\r?\n)*?)[ \t]*\[\[ @end \]\]\r?\n/m';

        do {
            $stub = preg_replace_callback($block, function (array $match) use ($flags) {
                foreach (explode('|', $match[1]) as $flag) {
                    if ($flags["@{$flag}"] ?? false) {
                        return $match[2];
                    }
                }

                return '';
            }, $stub, -1, $count);
        } while ($count > 0);

        $replace = array_diff_key($replace, $flags);

        return str_replace(array_keys($replace), array_values($replace), $stub);
    }

    /**
     * Write the files a stack gives, under a folder.
     *
     * @param  string  $directory
     * @param  array<int, array<string, mixed>>  $files  See Stack.
     * @param  array<string, string|bool>  $replace  The placeholders shared by the files.
     * @return void
     */
    protected function writeFiles(string $directory, array $files, array $replace = []): void
    {
        foreach ($files as $file) {
            $path = "{$directory}/{$file['path']}";

            if (($file['ensure'] ?? false) && $this->files->exists($path)) {
                continue;
            }

            $this->writeFile($path, $this->renderStub($file['stub'], [...$replace, ...($file['replace'] ?? [])]));
        }
    }

    /**
     * Write a panel module's summary page and its types, and the application's lang/{en,ar}/summary.php
     * when missing; its backend (ShowModuleCapability and its route) is generated by make:summary
     * first when the artifact has none.
     *
     * @param  Stack  $stack
     * @param  string  $module
     * @return void
     */
    protected function writeSummaryPage(Stack $stack, string $module): void
    {
        if (! $this->files->exists($this->ugarit->basePath("artifacts/{$module}/src/Capabilities/Module/ShowModuleCapability.php"))) {
            $this->call('make:summary', ['artifact' => $module]);
        }

        $this->writeFiles($this->modulePath($module), $stack->summaryFiles($module));

        foreach (['en', 'ar'] as $locale) {
            $path = $this->ugarit->langPath("{$locale}/summary.php");

            if (! $this->files->exists($path)) {
                $this->writeFile($path, $this->renderStub("lang/{$locale}/summary.php", []));
            }
        }
    }

    /**
     * Write a file, unless it exists and --force is not given.
     *
     * @param  string  $path
     * @param  string  $content
     * @return void
     */
    protected function writeFile(string $path, string $content): void
    {
        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->report[$path] = '<fg=gray>exists</>';

            return;
        }

        $status = $this->files->exists($path) ? '<fg=yellow>overwritten</>' : '<fg=green>created</>';
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);
        $this->report[$path] = $status;
    }

    /**
     * Edit a file, recording whether it was updated, unchanged or missing.
     *
     * @param  string  $path
     * @param  Closure(string): string  $edit
     * @return void
     */
    protected function editFile(string $path, Closure $edit): void
    {
        if (! $this->files->exists($path)) {
            $this->report[$path] = '<fg=yellow>missing</>';

            return;
        }

        $source = $this->files->get($path);
        $edited = $edit($source);

        if ($edited === $source) {
            $this->report[$path] ??= '<fg=gray>unchanged</>';

            return;
        }

        $this->files->put($path, $edited);
        $this->report[$path] = '<fg=green>updated</>';
    }

    /**
     * Apply the edits a stack gives: each adds its item to a list of a file, with its icons and imports.
     *
     * @param  Stack  $stack
     * @param  array<int, array<string, mixed>>  $edits  See Stack.
     * @return void
     */
    protected function applyEdits(Stack $stack, array $edits): void
    {
        foreach ($edits as $edit) {
            $path = $this->ugarit->basePath($edit['path']);

            $this->editFile($path, function (string $source) use ($stack, $path, $edit) {
                $edited = $this->addEntry($source, $path, $edit['opening'], $edit['key'], $edit['item'], $edit['place'], $edit['trailingComma'] ?? true);

                if ($edited === $source) {
                    return $source;
                }

                foreach ($edit['imports'] ?? [] as $key => $import) {
                    if (! str_contains($edited, $key)) {
                        $edited = $this->addScriptImport($edited, $import);
                    }
                }

                return ($edit['icons'] ?? []) === [] ? $edited : $this->addIcons($edited, $path, $edit['icons'], $stack->iconPackage());
            });
        }
    }

    /**
     * Add an item to a list unless the list holds its key already; a list that cannot be found is reported.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  string  $opening  A regex matching up to and including the opening "[", "(" or "{".
     * @param  string  $key  Text whose presence in the list means the item is there.
     * @param  string  $item
     * @param  string  $place  The list, as reported when it is not found.
     * @param  bool  $trailingComma
     * @return string
     */
    protected function addEntry(string $source, string $path, string $opening, string $key, string $item, string $place, bool $trailingComma = true): string
    {
        $content = $this->listContent($source, $opening);

        if ($content !== null && str_contains($content, $key)) {
            return $source;
        }

        $edited = $content === null ? null : $this->addToList($source, $opening, $item, null, $trailingComma);

        if ($edited === null) {
            $this->misses[] = "{$path}: {$place}";

            return $source;
        }

        return $edited;
    }

    /**
     * Add Lucide icons to the file's import from the stack's icon package.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  array<int, string>  $icons
     * @param  string  $package
     * @return string
     */
    protected function addIcons(string $source, string $path, array $icons, string $package): string
    {
        $opening = "/import \\{(?=[^}]*\\}\\s*from '".preg_quote($package, '/')."')/";

        foreach ($icons as $icon) {
            if (preg_match('/\b'.$icon.'\b/', (string) $this->listContent($source, $opening))) {
                continue;
            }

            $source = $this->addEntry($source, $path, $opening, "\0", $icon, "{$package} import");
        }

        return $source;
    }

    /**
     * Print the files written and edited, and the places to edit by hand, then forget them: the
     * command instance is shared, so a later call (make:panel --all) starts with an empty report.
     *
     * @return void
     */
    protected function printReport(): void
    {
        foreach ($this->report as $path => $status) {
            $this->components->twoColumnDetail(str_replace('\\', '/', Str::after($path, $this->ugarit->basePath().DIRECTORY_SEPARATOR)), $status);
        }

        foreach ($this->misses as $miss) {
            $this->components->warn('Not found, add it by hand: '.str_replace('\\', '/', Str::after($miss, $this->ugarit->basePath().DIRECTORY_SEPARATOR)));
        }

        $this->report = [];
        $this->misses = [];
    }

    /**
     * Sync the translations into the JSON files the frontend reads, then regenerate the Wayfinder
     * routes the pages import.
     *
     * @return void
     */
    protected function refreshFrontend(): void
    {
        $this->call('lang:sync');

        try {
            $this->callSilently('wayfinder:generate', ['--with-form' => true]);
            $this->components->info('Wayfinder routes regenerated.');
        } catch (Throwable $e) {
            $this->components->warn("Run php scribe wayfinder:generate --with-form: {$e->getMessage()}");
        }
    }

    /**
     * The Lucide icon name given by an option, in StudlyCase.
     *
     * @param  string|null  $icon
     * @param  string  $default
     * @return string
     */
    protected function iconName(?string $icon, string $default): string
    {
        return Str::studly($icon ?: $default);
    }
}
