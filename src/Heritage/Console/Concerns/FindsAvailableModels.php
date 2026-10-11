<?php

namespace Heritage\Console\Concerns;

use Heritage\Support\Collection;
use Symfony\Component\Finder\Finder;

trait FindsAvailableModels
{
    /**
     * Get a list of possible model names in the application or target modular artifact.
     *
     * @param  string|null  $artifact
     * @return array<int, string>
     */
    protected function findAvailableModels(?string $artifact = null)
    {
        if ($artifact) {
            $modelPath = $this->ugarit->basePath("artifacts/{$artifact}/src/Models");
            if (! is_dir($modelPath)) {
                $modelPath = $this->ugarit->basePath("artifacts/{$artifact}/src");
            }
        } else {
            $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();
        }

        if (! is_dir($modelPath)) {
            return [];
        }

        return (new Collection(Finder::create()->files()->depth(0)->in($modelPath)))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->filter(fn ($name) => ! str_ends_with($name, 'ServiceProvider') && ! str_ends_with($name, 'Controller') && ! str_ends_with($name, 'Request'))
            ->sort()
            ->values()
            ->all();
    }
}
