<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Concerns;

use Heritage\Support\Str;

/**
 * The --all of the Capability and Feature generators: everything an action needs to be reached,
 * each part by its own make: command. For "Settings/SetLocale":
 *
 * - its Result enum (Enums/Settings/{Layer}/SetLocaleResult, feedback "set_locale"),
 * - with input (--fields, or a route other than GET), its Parcel and its FormRequest #[ForAction],
 * - the action itself, wired to them,
 * - a controller method running it (Settings/SettingsController::setLocale()),
 * - its route in routes/web.php,
 * - its feedback messages in lang/en/responses.php,
 * - an empty component of the frontend stack (components/Settings/SetLocale).
 */
trait GeneratesActionSlice
{
    use ParsesInputFields, RegistersRoutes;

    /**
     * The folder of the action's companions below Enums, Parcels and Http/Requests.
     *
     * @return string "Capabilities" or "Features".
     */
    abstract protected function sliceLayer(): string;

    /**
     * Whether the action runs without input (a nullable Parcel).
     *
     * @return bool
     */
    abstract protected function sliceAcceptsNoInput(): bool;

    /**
     * Execute the console command, generating the action's slice with --all.
     *
     * @return bool|null
     */
    public function handle()
    {
        $all = $this->option('all') && ($this->option('force') || ! $this->alreadyExists($this->getNameInput()));

        if ($all) {
            $this->prepareSlice();
        }

        if (parent::handle() === false) {
            return false;
        }

        if ($all) {
            $this->completeSlice();
        }

        return null;
    }

    /**
     * Generate what the action is built on, its Result and Parcel, and point the options at them.
     *
     * @return void
     */
    protected function prepareSlice(): void
    {
        $prefix = $this->slicePath($this->sliceLayer());

        if (! $this->option('result')) {
            $this->generateSlicePart('make:result', ['name' => "{$prefix}Result", '--feedback' => $this->sliceFeedback()]);
            $this->input->setOption('result', "{$prefix}Result");
        }

        if ($this->sliceHasInput() && ! $this->option('parcel')) {
            $this->generateSlicePart('make:parcel', ['name' => "{$prefix}Parcel", '--fields' => $this->option('fields')]);
            $this->input->setOption('parcel', "{$prefix}Parcel");
        }
    }

    /**
     * Generate what reaches the action: its FormRequest, controller method, route, feedback
     * messages and component.
     *
     * @return void
     */
    protected function completeSlice(): void
    {
        $root = $this->rootNamespace();
        $action = $this->qualifyClass($this->getNameInput());
        $request = null;

        if ($this->sliceHasInput()) {
            $name = $this->slicePath($this->sliceLayer()).'Request';

            $this->generateSlicePart('make:request', [
                'name' => $name,
                '--'.($this->sliceLayer() === 'Features' ? 'feature' : 'capability') => true,
                '--action' => Str::after($action, $root),
                '--input' => $this->option('fields'),
            ]);

            $request = $root.'Http\\Requests\\'.str_replace('/', '\\', $name);
        }

        $controller = $this->sliceController();

        if (! $this->files->exists($this->getPath($controller))) {
            $this->generateSlicePart('make:controller', ['name' => Str::after($controller, $root.'Http\\Controllers\\')]);
        }

        $this->addControllerAction($controller, $action, $request);
        $this->registerSliceRoute($controller);
        $this->addFeedbackMessages();
        $this->generateSlicePart('make:component', ['name' => $this->slicePath()]);
    }

    /**
     * The folder and the base name of the action ("Settings/SetLocaleCapability" is
     * ["Settings", "SetLocale"]).
     *
     * @return array{0: string, 1: string}
     */
    protected function sliceName(): array
    {
        $name = preg_replace('/'.preg_quote($this->type, '/').'$/', '', str_replace('\\', '/', trim($this->getNameInput(), '\\/')));
        $segments = array_map(fn (string $segment) => Str::studly($segment), explode('/', $name));
        $base = array_pop($segments);

        return [implode('/', $segments), $base];
    }

    /**
     * The path of a companion class below its conventional folder, without its suffix
     * ("Settings/Capabilities/SetLocale"), or of the action itself without a layer.
     *
     * @param  string|null  $layer
     * @return string
     */
    protected function slicePath(?string $layer = null): string
    {
        [$folder, $base] = $this->sliceName();

        return implode('/', array_filter([$folder, $layer, $base]));
    }

    /**
     * Whether the action takes input: a Parcel and a FormRequest.
     *
     * @return bool
     */
    protected function sliceHasInput(): bool
    {
        return ! $this->sliceAcceptsNoInput() || $this->option('fields') || $this->option('parcel') || $this->sliceRoute()[0] !== 'get';
    }

    /**
     * The feedback key of the action's Result: --feedback, or its snake name ("set_locale").
     *
     * @return string
     */
    protected function sliceFeedback(): string
    {
        return (string) ($this->option('feedback') ?: Str::snake($this->sliceName()[1]));
    }

    /**
     * The verb and URI of the action's route: --route as "verb:uri" (or a URI alone), by default
     * POST with input and GET without, on the kebab name ("settings/set-locale").
     *
     * @return array{0: string, 1: string}
     */
    protected function sliceRoute(): array
    {
        $route = trim((string) $this->option('route'));

        if (preg_match('/^(get|post|put|patch|delete):(.*)$/i', $route, $match)) {
            return [strtolower($match[1]), trim($match[2], '/')];
        }

        $verb = ! $this->sliceAcceptsNoInput() || $this->option('fields') || $this->option('parcel') ? 'post' : 'get';
        $uri = $route !== '' ? trim($route, '/') : implode('/', array_map(fn (string $segment) => Str::kebab($segment), explode('/', $this->slicePath())));

        return [$verb, $uri];
    }

    /**
     * The controller running the action: --controller (relative to Http/Controllers), by default
     * the controller of its folder ("Settings/SettingsController"), or of its name without one.
     *
     * @return string The fully-qualified class.
     */
    protected function sliceController(): string
    {
        [$folder, $base] = $this->sliceName();
        $controller = $this->option('controller')
            ?: ($folder !== '' ? $folder.'/'.Str::afterLast($folder, '/') : $base);
        $controller = str_replace('/', '\\', trim((string) $controller, '\\/'));

        if (! str_ends_with($controller, 'Controller')) {
            $controller .= 'Controller';
        }

        return str_starts_with($controller, trim($this->rootNamespace(), '\\').'\\')
            ? $controller
            : $this->rootNamespace().'Http\\Controllers\\'.$controller;
    }

    /**
     * The controller method running the action: --method, or its camel name ("setLocale").
     *
     * @return string
     */
    protected function sliceMethod(): string
    {
        return (string) ($this->option('method') ?: Str::camel($this->sliceName()[1]));
    }

    /**
     * Add the method running the action to its controller: a page for a GET with --page, JSON for
     * another GET, otherwise a redirect back with its feedback.
     *
     * @param  string  $controller  The controller class.
     * @param  string  $action  The action class.
     * @param  string|null  $request  The FormRequest class, when the action takes input.
     * @return void
     */
    protected function addControllerAction(string $controller, string $action, ?string $request): void
    {
        $path = $this->getPath($controller);
        $method = $this->sliceMethod();
        $source = $this->files->get($path);

        if (preg_match('/function\s+'.preg_quote($method, '/').'\s*\(/', $source)) {
            $this->components->info(sprintf('Controller [%s] already has [%s()].', $path, $method));

            return;
        }

        $parcel = $request ? $this->companionClass('parcel', 'Parcels') : null;
        $resource = $this->companionClass('resource', 'Http\\Resources');
        $page = (string) $this->option('page');
        $name = class_basename($action);
        $arguments = "{$name}::class, ".($parcel ? class_basename($parcel).'::fromArray($request->validated())' : 'null');
        $presenter = $resource ? ', '.class_basename($resource).'::class' : '';

        [$summary, $call] = match (true) {
            $this->sliceRoute()[0] !== 'get' => ["Run {$name} and redirect back with its feedback.", "\$this->back({$arguments}{$presenter})"],
            $page !== '' => ["Run {$name} and render its page.", "\$this->page({$arguments}, '{$page}'{$presenter})"],
            default => ["Run {$name} and answer JSON.", "\$this->json({$arguments}{$presenter})"],
        };

        $eol = str_contains($source, "\r\n") ? "\r\n" : "\n";
        $lines = [
            '    /**',
            "     * {$summary}",
            '     *',
            ...($request ? ['     * @param  '.class_basename($request).'  $request'] : []),
            '     * @return mixed',
            '     */',
            "    public function {$method}(".($request ? class_basename($request).' $request' : '').')',
            '    {',
            "        return {$call};",
            '    }',
        ];
        $body = implode($eol, $lines);

        $source = preg_match('/^    \/\/\R(?=\}\s*$)/m', $source)
            ? preg_replace('/^    \/\/\R(?=\}\s*$)/m', $body.$eol, $source, 1)
            : preg_replace('/\R\}\s*$/', $eol.$eol.$body.$eol.'}'.$eol, $source, 1);

        $this->files->put($path, $this->addImports($source, [$action, $request, $parcel, $resource]));
        $this->components->info(sprintf('Controller [%s] runs %s in [%s()].', $path, $name, $method));
    }

    /**
     * Merge classes into the sorted "use" block heading a class.
     *
     * @param  string  $source
     * @param  array<int, string|null>  $classes
     * @return string
     */
    protected function addImports(string $source, array $classes): string
    {
        $eol = str_contains($source, "\r\n") ? "\r\n" : "\n";
        preg_match_all('/^use [^;\r\n]+;/m', $source, $matches);

        $existing = $matches[0];
        $imports = array_values(array_unique([...$existing, ...array_map(fn (string $class) => "use {$class};", array_filter($classes))]));
        sort($imports);

        if ($existing === []) {
            return preg_replace('/^(namespace [^;]+;\R)/m', '$1'.$eol.implode($eol, $imports).$eol, $source, 1);
        }

        $start = strpos($source, $existing[0]);
        $end = strrpos($source, end($existing)) + strlen(end($existing));

        return substr_replace($source, implode($eol, $imports), $start, $end - $start);
    }

    /**
     * Register the action's route, named by --route-name or its dotted kebab name.
     *
     * @param  string  $controller  The controller class.
     * @return void
     */
    protected function registerSliceRoute(string $controller): void
    {
        [$verb, $uri] = $this->sliceRoute();
        $alias = $this->controllerAlias($controller);
        $name = $this->option('route-name') ?: str_replace('/', '.', implode('/', array_map(fn (string $segment) => Str::kebab($segment), explode('/', $this->slicePath()))));

        $this->registerRoutes([
            $alias => [$controller, "Route::{$verb}('{$uri}', [{$alias}::class, '{$this->sliceMethod()}'])->name('{$name}');"],
        ]);
    }

    /**
     * The alias a routes file imports a controller by: its path below Http/Controllers, a folder
     * the next segment starts with left out ("Settings\LocaleController" is
     * "SettingsLocaleController", "Settings\SettingsController" is "SettingsController").
     *
     * @param  string  $controller
     * @return string
     */
    protected function controllerAlias(string $controller): string
    {
        $segments = explode('\\', Str::after($controller, $this->rootNamespace().'Http\\Controllers\\'));
        $alias = '';

        foreach ($segments as $index => $segment) {
            if (! str_starts_with($segments[$index + 1] ?? '', $segment)) {
                $alias .= $segment;
            }
        }

        return $alias;
    }

    /**
     * Add the action's success and failure messages to lang/en/responses.php, unless given.
     *
     * @return void
     */
    protected function addFeedbackMessages(): void
    {
        $feedback = $this->sliceFeedback();
        $path = $this->ugarit->langPath('en/responses.php');

        if (str_contains($feedback, '.') || ! $this->files->exists($path)) {
            $this->components->warn("Write the feedback messages responses.{$feedback} and its failure yourself.");

            return;
        }

        $source = $this->files->get($path);
        $eol = str_contains($source, "\r\n") ? "\r\n" : "\n";
        $label = Str::ucfirst(Str::lower(Str::headline($this->sliceName()[1])));
        $failed = "failed_{$feedback}";
        $messages = [
            $feedback => ["{$label} succeeded", "{$label} completed successfully."],
            $failed => ["{$label} failed", "{$label} could not be completed. Please try again."],
        ];
        $entries = '';

        foreach ($messages as $key => [$title, $message]) {
            if (preg_match("/^\\s*'".preg_quote($key, '/')."'\\s*=>/m", $source)) {
                continue;
            }

            $entries .= $eol."    '{$key}' => [{$eol}        'title' => '{$title}',{$eol}        'message' => '{$message}',{$eol}    ],{$eol}";
        }

        if ($entries === '') {
            $this->components->info(sprintf('Lang [%s] already has responses.%s.', $path, $feedback));

            return;
        }

        $this->files->put($path, substr_replace($source, $entries, (int) strrpos($source, '];'), 0));
        $this->components->info(sprintf('Lang [%s] has responses.%s and responses.%s.', $path, $feedback, $failed));
    }

    /**
     * Run a generator of the slice with the action's artifact and --force, without prompting.
     *
     * @param  string  $command
     * @param  array<string, mixed>  $arguments
     * @return void
     */
    protected function generateSlicePart(string $command, array $arguments): void
    {
        $this->call($command, array_filter([
            ...$arguments,
            '--artifact' => $this->getArtifactOption(),
            '--force' => (bool) $this->option('force'),
            '--no-interaction' => true,
        ], fn ($value) => $value !== null && $value !== false && $value !== ''));
    }
}
