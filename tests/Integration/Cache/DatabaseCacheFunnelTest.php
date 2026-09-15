<?php

namespace Heritage\Tests\Integration\Cache;

use Heritage\Contracts\Cache\Repository;
use Heritage\Foundation\Testing\LazilyRefreshDatabase;
use Heritage\Support\Facades\Cache;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('cache')]
class DatabaseCacheFunnelTest extends CacheFunnelTestCase
{
    use LazilyRefreshDatabase;

    protected function cache(): Repository
    {
        return Cache::store('database');
    }
}
