<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console;

use Heritage\Console\Command;
use Heritage\Filesystem\Filesystem;
use Heritage\Support\Arr;
use Heritage\Translation\Locales;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'lang:sync')]
class LangSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:sync
                    {--fresh : Rebuild the JSON files from the PHP translations only, dropping the keys they do not give}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the PHP translations of the application and its artifacts into lang/{locale}.json';

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
     * Execute the console command: every available locale's JSON file receives the application's
     * lang/{locale}/*.php ("file.key") and the artifacts' ("artifact::file.key"), its other keys
     * kept unless --fresh is given.
     *
     * @return int
     */
    public function handle(): int
    {
        foreach (Locales::resolver()->available() as $locale) {
            $path = $this->ugarit->langPath("{$locale}.json");
            $existing = $this->files->exists($path) ? (array) json_decode($this->files->get($path), true) : [];
            $translations = $this->translations($locale);
            $synced = $this->option('fresh') ? $translations : [...$existing, ...$translations];
            $changed = count(array_filter($synced, fn ($value, $key) => ! array_key_exists($key, $existing) || $existing[$key] !== $value, ARRAY_FILTER_USE_BOTH))
                + count(array_diff_key($existing, $synced));

            if ($changed === 0) {
                $this->components->twoColumnDetail("lang/{$locale}.json", '<fg=gray>unchanged</>');

                continue;
            }

            $this->files->put($path, json_encode($synced, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
            $this->components->twoColumnDetail("lang/{$locale}.json", "<fg=green>{$changed} keys</>");
        }

        return self::SUCCESS;
    }

    /**
     * The translations of a locale, flattened as dotted keys: the application's, then each artifact's
     * prefixed by its name.
     *
     * @param  string  $locale
     * @return array<string, mixed>
     */
    protected function translations(string $locale): array
    {
        $translations = $this->load($this->ugarit->langPath($locale), '');
        $artifacts = $this->ugarit->basePath('artifacts');

        foreach ($this->files->isDirectory($artifacts) ? $this->files->directories($artifacts) : [] as $artifact) {
            $translations += $this->load("{$artifact}/lang/{$locale}", basename($artifact).'::');
        }

        return $translations;
    }

    /**
     * The translations of the PHP files of a folder, as "{prefix}{file}.{key}" (null as empty).
     *
     * @param  string  $directory
     * @param  string  $prefix
     * @return array<string, mixed>
     */
    protected function load(string $directory, string $prefix): array
    {
        $translations = [];

        if (! $this->files->isDirectory($directory)) {
            return $translations;
        }

        foreach ($this->files->files($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $data = require $file->getPathname();

            if (is_array($data)) {
                foreach (Arr::dot($data) as $key => $value) {
                    $translations[$prefix.$file->getBasename('.php').".{$key}"] = $value ?? '';
                }
            }
        }

        return $translations;
    }
}
