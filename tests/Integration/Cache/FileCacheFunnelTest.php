<?php

namespace Heritage\Tests\Integration\Cache;

use Heritage\Contracts\Cache\Repository;
use Heritage\Support\Facades\Cache;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('cache.default', 'file')]
class FileCacheFunnelTest extends CacheFunnelTestCase
{
    protected function cache(): Repository
    {
        return Cache::store('file');
    }
}
