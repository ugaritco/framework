<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class RefreshCommandTest extends TestCase
{
    public function testRefreshWithoutRealpath()
    {
        $this->app->setBasePath(__DIR__);

        $options = [
            '--path' => 'Fixtures/',
        ];

        $this->migrateRefreshWith($options);
    }

    public function testRefreshWithRealpath()
    {
        $options = [
            '--path' => realpath(__DIR__.'/Fixtures/'),
            '--realpath' => true,
        ];

        $this->migrateRefreshWith($options);
    }

    private function migrateRefreshWith(array $options)
    {
        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->scribe('db:wipe', ['--drop-views' => true]);
        }

        $this->beforeApplicationDestroyed(function () use ($options) {
            $this->scribe('migrate:rollback', $options);
        });

        $this->scribe('migrate:refresh', $options);
        DB::table('members')->insert(['name' => 'foo', 'email' => 'foo@bar', 'password' => 'secret']);
        $this->assertEquals(1, DB::table('members')->count());

        $this->scribe('migrate:refresh', $options);
        $this->assertEquals(0, DB::table('members')->count());
    }
}
