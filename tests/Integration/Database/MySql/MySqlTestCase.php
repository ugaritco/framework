<?php

namespace Heritage\Tests\Integration\Database\MySql;

use Heritage\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('mysql')]
abstract class MySqlTestCase extends DatabaseTestCase
{
    //
}
