<?php

namespace Heritage\Foundation\Testing;

use Heritage\Contracts\Console\Kernel;
use Heritage\Foundation\Testing\Traits\CanConfigureMigrationCommands;

trait DatabaseMigrations
{
    use CanConfigureMigrationCommands;

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();

        $this->beforeApplicationDestroyed(function () {
            $this->scribe('migrate:rollback');

            RefreshDatabaseState::$migrated = false;
        });
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        $this->scribe('migrate:fresh', $this->migrateFreshUsing());

        $this->app[Kernel::class]->setScribe(null);
    }

    /**
     * Perform any work that should take place before the database has started refreshing.
     *
     * @return void
     */
    protected function beforeRefreshingDatabase()
    {
        // ...
    }

    /**
     * Perform any work that should take place once the database has finished refreshing.
     *
     * @return void
     */
    protected function afterRefreshingDatabase()
    {
        // ...
    }
}
