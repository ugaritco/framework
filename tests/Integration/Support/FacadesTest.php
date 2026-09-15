<?php

namespace Heritage\Tests\Integration\Support;

use Heritage\Auth\AuthManager;
use Heritage\Foundation\Application;
use Heritage\Support\Collection;
use Heritage\Support\Facades\Auth;
use Heritage\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class FacadesTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['__ugarit.authResolved']);

        parent::tearDown();
    }

    public function testFacadeResolvedCanResolveCallback()
    {
        Auth::resolved(function (AuthManager $auth, Application $app) {
            $_SERVER['__ugarit.authResolved'] = true;
        });

        $this->assertFalse(isset($_SERVER['__ugarit.authResolved']));

        $this->app->make('auth');

        $this->assertTrue(isset($_SERVER['__ugarit.authResolved']));
    }

    public function testFacadeResolvedCanResolveCallbackAfterAccessRootHasBeenResolved()
    {
        $this->app->make('auth');

        $this->assertFalse(isset($_SERVER['__ugarit.authResolved']));

        Auth::resolved(function (AuthManager $auth, Application $app) {
            $_SERVER['__ugarit.authResolved'] = true;
        });

        $this->assertTrue(isset($_SERVER['__ugarit.authResolved']));
    }

    public function testDefaultAliases()
    {
        $defaultAliases = Facade::defaultAliases();

        $this->assertInstanceOf(Collection::class, $defaultAliases);

        foreach ($defaultAliases as $alias => $abstract) {
            $this->assertTrue(class_exists($alias));
            $this->assertTrue(class_exists($abstract));

            $reflection = new ReflectionClass($alias);
            $this->assertSame($abstract, $reflection->getName());
        }
    }
}
