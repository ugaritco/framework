<?php

declare(strict_types=1);

namespace Heritage\Foundation\Support\Providers;

use Heritage\Support\ServiceProvider;

/**
 * Loads the resources of every discovered artifact by convention, so an artifact's own service
 * provider only binds its services:
 *
 * | Path                    | Loaded as                                        |
 * | :---------------------- | :----------------------------------------------- |
 * | `config/*.php`          | merged under the file's name (`config/geography.php` → `geography`) |
 * | `database/migrations`   | a migration path                                 |
 * | `lang`                  | translations under the artifact id (`library::genre.label`) |
 * | `resources/views`       | views under the artifact id (`library::index`)   |
 * | `routes/web.php`, `routes/api.php` | route files, unless the routes are cached |
 *
 * A missing path is skipped. The configuration files are published with the `{id}-config` tag,
 * and the artifact's `{Studly}DatabaseSeeder` is found by Seeder::discoverArtifactSeeders().
 */
class ArtifactResourcesServiceProvider extends ServiceProvider
{
    /**
     * The route files of an artifact, relative to its root, in loading order.
     *
     * @var array<int, string>
     */
    protected const ROUTE_FILES = ['routes/web.php', 'routes/api.php'];

    /**
     * The artifacts whose resources are loaded.
     *
     * @var array<string, string> Artifact id => absolute root path.
     */
    protected array $artifacts = [];

    /**
     * Add an artifact whose resources are loaded.
     *
     * @param  string  $id  The artifact id, also the namespace of its translations and views.
     * @param  string  $path  The absolute root path of the artifact.
     * @return $this
     */
    public function addArtifact(string $id, string $path): static
    {
        $this->artifacts[$id] = rtrim($path, '/\\');

        return $this;
    }

    /**
     * The artifacts whose resources are loaded.
     *
     * @return array<string, string> Artifact id => absolute root path.
     */
    public function artifacts(): array
    {
        return $this->artifacts;
    }

    /**
     * Merge the configuration files of every artifact.
     *
     * @return void
     */
    public function register(): void
    {
        foreach ($this->artifacts as $path) {
            foreach (glob($path.'/config/*.php') ?: [] as $file) {
                $this->mergeConfigFrom($file, pathinfo($file, PATHINFO_FILENAME));
            }
        }
    }

    /**
     * Load the migrations, translations, views and routes of every artifact, and offer its
     * configuration files to `vendor:publish --tag={id}-config`.
     *
     * @return void
     */
    public function boot(): void
    {
        foreach ($this->artifacts as $id => $path) {
            if ($this->app->runningInConsole() && ($configs = glob($path.'/config/*.php') ?: []) !== []) {
                $this->publishes(
                    array_combine($configs, array_map(fn (string $file) => $this->app->configPath(basename($file)), $configs)),
                    "{$id}-config"
                );
            }

            if (is_dir($migrations = $path.'/database/migrations')) {
                $this->loadMigrationsFrom($migrations);
            }

            if (is_dir($lang = $path.'/lang')) {
                $this->loadTranslationsFrom($lang, $id);
            }

            if (is_dir($views = $path.'/resources/views')) {
                $this->loadViewsFrom($views, $id);
            }

            foreach (static::ROUTE_FILES as $routes) {
                if (is_file($file = $path.'/'.$routes)) {
                    $this->loadRoutesFrom($file);
                }
            }
        }
    }
}
