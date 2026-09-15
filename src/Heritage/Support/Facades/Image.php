<?php

namespace Heritage\Support\Facades;

/**
 * @method static \Heritage\Image\Image fromBytes(string $contents)
 * @method static \Heritage\Image\Image fromStream(resource $stream)
 * @method static \Heritage\Image\Image fromBase64(string $base64)
 * @method static \Heritage\Image\Image fromPath(string $path)
 * @method static \Heritage\Image\Image fromStorage(string $path, \BackedEnum|string|null $disk = null)
 * @method static \Heritage\Image\Image fromUpload(\Heritage\Http\UploadedFile $file)
 * @method static \Heritage\Image\Image fromUrl(string $url)
 * @method static \Heritage\Image\ImageManager transformUsing(string $driver, string $transformation, callable $callback)
 * @method static string getDefaultDriver()
 * @method static mixed driver(\UnitEnum|string|null $driver = null)
 * @method static \Heritage\Image\ImageManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Heritage\Contracts\Container\Container getContainer()
 * @method static \Heritage\Image\ImageManager setContainer(\Heritage\Contracts\Container\Container $container)
 * @method static \Heritage\Image\ImageManager forgetDrivers()
 *
 * @see \Heritage\Image\ImageManager
 */
class Image extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'image';
    }
}
