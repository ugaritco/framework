<?php

namespace Heritage\Tests\Integration\Cache;

use Heritage\Contracts\Cache\Repository;
use Heritage\Foundation\Testing\Concerns\InteractsWithRedis;
use Heritage\Support\Facades\Cache;

class RedisCacheFunnelTest extends CacheFunnelTestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        Cache::purge('redis');

        $this->releaseFunnelLocks();
    }

    protected function cache(): Repository
    {
        return Cache::store('redis');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }
}
