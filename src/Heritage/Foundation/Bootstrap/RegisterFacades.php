<?php

namespace Heritage\Foundation\Bootstrap;

use Heritage\Contracts\Foundation\Application;
use Heritage\Foundation\AliasLoader;
use Heritage\Foundation\PackageManifest;
use Heritage\Support\Facades\Facade;

class RegisterFacades
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Heritage\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($app);

        AliasLoader::getInstance(array_merge(
            $app->make('config')->get('app.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
