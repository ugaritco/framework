<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\View\FileViewFinder;
use InvalidArgumentException;

/**
 * Class PageFinder
 *
 * Finds the file of an Inertia page among the pages of the application it is named after.
 *
 * Each application of the frontend (web, auth, panel) owns its pages and layouts under
 * resources/js/apps/{app}, so a page is named after its application:
 * 1. Application notation: "app/PagePath" (e.g. "panel/Dashboard", "auth/Login") is
 *    resources/js/apps/{app}/pages/{PagePath}.
 * 2. Artifact notation: "artifact::PagePath" (e.g. "i18n::language/Index") is a page of the
 *    artifact's panel, resources/js/apps/panel/artifacts/{artifact}/pages, or of its package,
 *    artifacts/{artifact}/resources/js/pages.
 * A frontend without resources/js/apps keeps finding its pages in the given paths
 * (config('inertia.pages.paths'), resources/js/pages by default).
 */
class PageFinder extends FileViewFinder
{
    /**
     * The extensions of a page file when none are given.
     *
     * @var string[]
     */
    public const EXTENSIONS = ['vue', 'jsx', 'tsx', 'svelte', 'js', 'ts'];

    /**
     * Get the path of the page's file.
     *
     * @param  string  $view  The page name (e.g. "panel/Dashboard" or "i18n::language/Index").
     * @return string
     *
     * @throws InvalidArgumentException If the page has no file in its pages directory.
     */
    public function find($view)
    {
        $view = trim($view);

        if (isset($this->views[$view])) {
            return $this->views[$view];
        }

        $directories = $this->pageDirectories($view);

        if ($directories === null) {
            return $this->views[$view] = $this->findInPaths($view, $this->paths);
        }

        [$directories, $path] = $directories;

        foreach ($directories as $directory) {
            foreach ($this->pageCandidates($path) as $candidate) {
                foreach ($this->extensions as $extension) {
                    if ($this->files->exists("{$directory}/{$candidate}.{$extension}")) {
                        return $this->views[$view] = "{$directory}/{$candidate}.{$extension}";
                    }
                }
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Inertia page [%s] was not found in [%s]; a page is named after its application ("panel/Dashboard") or artifact ("i18n::language/Index").',
            $view,
            implode('], [', $directories)
        ));
    }

    /**
     * Get the directories holding the page and its path within them, or null when the page is
     * found in the finder's paths (a frontend without resources/js/apps).
     *
     * @param  string  $page  The page name.
     * @return array{0: array<int, string>, 1: string}|null
     */
    protected function pageDirectories(string $page): ?array
    {
        if (str_contains($page, '::')) {
            [$artifact, $path] = explode('::', $page, 2);

            return [[
                resource_path("js/apps/panel/artifacts/{$artifact}/pages"),
                base_path("artifacts/{$artifact}/resources/js/pages"),
            ], $path];
        }

        if (! is_dir(resource_path('js/apps'))) {
            return null;
        }

        [$app, $path] = array_pad(explode('/', $page, 2), 2, '');

        return [[resource_path("js/apps/{$app}/pages")], $path];
    }

    /**
     * Get the paths a page may have: as named, and with its file name capitalized ("settings/profile"
     * is also "settings/Profile").
     *
     * @param  string  $path  The page path within its pages directory.
     * @return array<int, string>
     */
    protected function pageCandidates(string $path): array
    {
        $segments = explode('/', $path);
        $segments[] = ucfirst((string) array_pop($segments));

        return array_values(array_unique([$path, implode('/', $segments)]));
    }
}
