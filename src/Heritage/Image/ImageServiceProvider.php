<?php

namespace Heritage\Image;

use Heritage\Contracts\Support\DeferrableProvider;
use Heritage\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->scoped('image', function ($app) {
            return new ImageManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['image'];
    }
}
