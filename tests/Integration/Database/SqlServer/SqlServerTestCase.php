<?php

namespace Heritage\Tests\Integration\Database\SqlServer;

use Heritage\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('sqlsrv')]
abstract class SqlServerTestCase extends DatabaseTestCase
{
    //
}
