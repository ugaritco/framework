<?php

namespace Heritage\Tests\Integration\Database\Sqlite\Console;

use Heritage\Filesystem\Filesystem;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use Orchestra\Testbench\Attributes\WithConfig;

use function Heritage\Filesystem\join_paths;
use function Orchestra\Testbench\default_migration_path;
use function Orchestra\Testbench\default_skeleton_path;

#[RequiresDatabase('sqlite')]
#[WithConfig('database.connections.sqlite.journal_mode', 'wal')]
class MigrateFreshCommandWithJournalModeWalTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        $files = new Filesystem;

        $files->copy(
            join_paths(__DIR__, 'Fixtures', 'database-journal-mode-wal.sqlite'),
            join_paths(default_skeleton_path(), 'database', 'database.sqlite')
        );

        $this->beforeApplicationDestroyed(function () use ($files) {
            $files->delete(database_path('database.sqlite'));
        });

        parent::setUp();
    }

    public function testRunningMigrateFreshCommandWithWalJournalMode()
    {
        $this->scribe('migrate:fresh', [
            '--realpath' => true,
            '--path' => default_migration_path(),
        ])->run();

        $this->assertTrue(Schema::hasTable('users'));
    }
}
