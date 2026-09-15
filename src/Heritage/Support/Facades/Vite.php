<?php

namespace Heritage\Support\Facades;

/**
 * @method static array preloadedAssets()
 * @method static string|null cspNonce()
 * @method static string useCspNonce(string|null $nonce = null)
 * @method static \Heritage\Foundation\Vite useIntegrityKey(string|false $key)
 * @method static \Heritage\Foundation\Vite withEntryPoints(array $entryPoints)
 * @method static \Heritage\Foundation\Vite mergeEntryPoints(array $entryPoints)
 * @method static \Heritage\Foundation\Vite useManifestFilename(string $filename)
 * @method static \Heritage\Foundation\Vite createAssetPathsUsing(callable|null $resolver)
 * @method static string hotFile()
 * @method static \Heritage\Foundation\Vite useHotFile(string $path)
 * @method static \Heritage\Foundation\Vite useBuildDirectory(string $path)
 * @method static \Heritage\Foundation\Vite useScriptTagAttributes(callable|array $attributes)
 * @method static \Heritage\Foundation\Vite useStyleTagAttributes(callable|array $attributes)
 * @method static \Heritage\Foundation\Vite usePreloadTagAttributes(callable|array|false $attributes)
 * @method static \Heritage\Foundation\Vite prefetch(int|null $concurrency = null, string $event = 'load')
 * @method static \Heritage\Foundation\Vite useWaterfallPrefetching(int|null $concurrency = null)
 * @method static \Heritage\Foundation\Vite useAggressivePrefetching()
 * @method static \Heritage\Foundation\Vite usePrefetchStrategy(string|null $strategy, array $config = [])
 * @method static \Heritage\Support\HtmlString|void reactRefresh()
 * @method static string|null devServerUrl()
 * @method static string asset(string $asset, string|null $buildDirectory = null)
 * @method static string content(string $asset, string|null $buildDirectory = null)
 * @method static string|null manifestHash(string|null $buildDirectory = null)
 * @method static \Heritage\Support\HtmlString fonts(array|string|null $aliases = null)
 * @method static \Heritage\Foundation\Vite useFontsManifestFilename(string $filename)
 * @method static bool isRunningHot()
 * @method static string toHtml()
 * @method static void flush()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Heritage\Foundation\Vite
 */
class Vite extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Heritage\Foundation\Vite::class;
    }
}
