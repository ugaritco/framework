<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Concerns;

use Heritage\Support\Str;

/**
 * Registers generated routes in routes/web.php: the artifact's (grouped under its panel prefix),
 * or the application's.
 */
trait RegistersRoutes
{
    /**
     * Register routes in routes/web.php: inside its last group, or in a new file grouping them
     * under the web and auth middleware.
     *
     * @param  array<string, array{0: class-string, 1: string}>  $routes  Alias => [controller, route].
     * @return void
     */
    protected function registerRoutes(array $routes): void
    {
        $artifact = $this->getArtifactOption();
        $path = $artifact ? $this->ugarit->basePath("artifacts/{$artifact}/routes/web.php") : $this->ugarit->basePath('routes/web.php');

        if (! $this->files->exists($path)) {
            $imports = implode("\n", array_map(fn (string $alias) => $this->routeImport($routes[$alias][0], $alias), array_keys($routes)));
            $lines = implode("\n\n", array_map(fn (array $route) => "    {$route[1]}", $routes));
            $group = $artifact ? "Route::middleware(['web', 'auth'])->prefix('panel/artifact/{$artifact}')" : "Route::middleware(['web', 'auth'])";

            $this->makeDirectory($path);
            $this->files->put($path, "<?php\n\nuse Heritage\\Support\\Facades\\Route;\n{$imports}\n\n{$group}->group(function () {\n{$lines}\n});\n");
            $this->components->info(sprintf('Routes [%s] created with [%s].', $path, implode('], [', array_column($routes, 1))));

            return;
        }

        $content = $this->files->get($path);

        foreach ($routes as $alias => [$controller, $route]) {
            if (str_contains($content, Str::before($route, ',').',')) {
                $this->components->info(sprintf('Routes [%s] already register [%s].', $path, Str::betweenFirst($route, "'", "'")));

                continue;
            }

            $content = $this->insertRoute($content, $this->routeImport($controller, $alias), $route);
            $this->components->info(sprintf('Routes [%s] register [%s].', $path, $route));
        }

        $this->files->put($path, $content);
    }

    /**
     * The "use" statement importing a controller by its alias.
     *
     * @param  class-string  $controller
     * @param  string  $alias
     * @return string
     */
    protected function routeImport(string $controller, string $alias): string
    {
        return class_basename($controller) === $alias ? "use {$controller};" : "use {$controller} as {$alias};";
    }

    /**
     * Insert a route at the end of the last group of a routes file, and its controller import
     * after the last one.
     *
     * @param  string  $content  The routes file.
     * @param  string  $import  The "use" statement of the controller.
     * @param  string  $route
     * @return string
     */
    protected function insertRoute(string $content, string $import, string $route): string
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";

        if (! str_contains($content, $import)) {
            if (preg_match_all('/^use [^;]+;/m', $content, $uses, PREG_OFFSET_CAPTURE)) {
                [$last, $offset] = end($uses[0]);
                $content = substr_replace($content, $eol.$import, $offset + strlen($last), 0);
            } else {
                $content = preg_replace('/^<\?php\r?\n/', '<?php'.$eol.$eol.$import.$eol, $content, 1);
            }
        }

        return preg_match_all('/^\}\);/m', $content, $closings, PREG_OFFSET_CAPTURE)
            ? substr_replace($content, "{$eol}    {$route}{$eol}", end($closings[0])[1], 0)
            : rtrim($content).$eol.$eol.$route.$eol;
    }
}
