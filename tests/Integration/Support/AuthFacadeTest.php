<?php

namespace Heritage\Tests\Integration\Support;

use Heritage\Support\Facades\Auth;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class AuthFacadeTest extends TestCase
{
    public function testItFailsIfTheUiPackageIsMissing()
    {
        $this->expectExceptionObject(new RuntimeException(
            'In order to use the Auth::routes() method, please install the ugarit/ui package.'
        ));

        Auth::routes();
    }
}
