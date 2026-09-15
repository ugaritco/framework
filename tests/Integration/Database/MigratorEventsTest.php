<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Database\Events\MigrationEnded;
use Heritage\Database\Events\MigrationsEnded;
use Heritage\Database\Events\MigrationSkipped;
use Heritage\Database\Events\MigrationsStarted;
use Heritage\Database\Events\MigrationStarted;
use Heritage\Database\Events\NoPendingMigrations;
use Heritage\Database\Migrations\Migration;
use Heritage\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class MigratorEventsTest extends TestCase
{
    protected function migrateOptions()
    {
        return [
            '--path' => realpath(__DIR__.'/Fixtures/'),
            '--realpath' => true,
        ];
    }

    public function testMigrationEventsAreFired()
    {
        Event::fake();

        $this->scribe('migrate', $this->migrateOptions());
        $this->scribe('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, 2);
        Event::assertDispatched(MigrationsEnded::class, 2);
        Event::assertDispatched(MigrationStarted::class, 2);
        Event::assertDispatched(MigrationEnded::class, 2);
        Event::assertDispatched(MigrationSkipped::class, 1);
    }

    public function testMigrationEventsContainTheOptionsAndPretendFalse()
    {
        Event::fake();

        $this->scribe('migrate', $this->migrateOptions());
        $this->scribe('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'down'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'down'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
    }

    public function testMigrationEventsContainTheOptionsAndPretendTrue()
    {
        Event::fake();

        $this->scribe('migrate', $this->migrateOptions() + ['--pretend' => true]);
        $this->scribe('migrate:rollback', $this->migrateOptions()); // doesn't support pretend

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === true;
        });

        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === true;
        });
    }

    public function testMigrationEventsContainTheMigrationAndMethod()
    {
        Event::fake();

        $this->scribe('migrate', $this->migrateOptions());
        $this->scribe('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'up';
        });
        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'down';
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'up';
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'down';
        });

        Event::assertDispatched(MigrationStarted::class, function ($event) {
            return $event->method === 'up'
                && $event->migration instanceof Migration
                && $event->name === '2014_10_12_000000_create_members_table';
        });
        Event::assertDispatched(MigrationStarted::class, function ($event) {
            return $event->method === 'down'
                && $event->migration instanceof Migration
                && $event->name === '2014_10_12_000000_create_members_table';
        });
        Event::assertDispatched(MigrationEnded::class, function ($event) {
            return $event->method === 'up'
                && $event->migration instanceof Migration
                && $event->name === '2014_10_12_000000_create_members_table';
        });
        Event::assertDispatched(MigrationEnded::class, function ($event) {
            return $event->method === 'down'
                && $event->migration instanceof Migration
                && $event->name === '2014_10_12_000000_create_members_table';
        });
    }

    public function testTheNoMigrationEventIsFiredWhenNothingToMigrate()
    {
        Event::fake();

        $this->scribe('migrate');
        $this->scribe('migrate:rollback');

        Event::assertDispatched(NoPendingMigrations::class, function ($event) {
            return $event->method === 'up';
        });
        Event::assertDispatched(NoPendingMigrations::class, function ($event) {
            return $event->method === 'down';
        });
    }

    public function testMigrationSkippedEventIsFired()
    {
        Event::fake();

        $this->scribe('migrate', [
            '--path' => realpath(__DIR__.'/Fixtures/2014_10_13_000000_skipped_migration.php'),
            '--realpath' => true,
        ]);

        Event::assertDispatched(MigrationSkipped::class, function ($event) {
            return $event->migrationName === '2014_10_13_000000_skipped_migration';
        });
    }
}
