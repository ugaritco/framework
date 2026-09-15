<?php

namespace Heritage\Foundation\Bootstrap;

use Heritage\Contracts\Foundation\Application;

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Heritage\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->boot();
    }
}
