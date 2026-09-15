<?php

namespace Heritage\Tests\Integration\Database\MariaDb;

use Heritage\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('mariadb')]
abstract class MariaDbTestCase extends DatabaseTestCase
{
    //
}
