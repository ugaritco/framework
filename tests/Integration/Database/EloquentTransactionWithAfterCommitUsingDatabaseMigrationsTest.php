<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Foundation\Testing\DatabaseMigrations;

class EloquentTransactionWithAfterCommitUsingDatabaseMigrationsTest extends DatabaseTestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use DatabaseMigrations;
}
