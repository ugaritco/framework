<?php

namespace Heritage\Tests\Integration\Database\Postgres;

use Heritage\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('pgsql')]
abstract class PostgresTestCase extends DatabaseTestCase
{
    //
}
