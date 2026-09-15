<?php

namespace Heritage\Tests\Integration\Support\Fixtures;

use Heritage\Support\Manager;

class NullableManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string|null
     */
    public function getDefaultDriver()
    {
        //
    }
}
