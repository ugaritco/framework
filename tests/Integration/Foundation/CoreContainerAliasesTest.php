<?php

namespace Heritage\Tests\Integration\Foundation;

use Heritage\Database\ConnectionResolverInterface;
use Heritage\Database\DatabaseManager;
use Orchestra\Testbench\TestCase;

class CoreContainerAliasesTest extends TestCase
{
    public function testItCanResolveCoreContainerAliases()
    {
        $this->assertInstanceOf(DatabaseManager::class, $this->app->make(ConnectionResolverInterface::class));
    }
}
