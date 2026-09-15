<?php

declare(strict_types=1);

use Heritage\Redis\RedisManager;

use function PHPStan\Testing\assertType;

$redisManager = resolve(RedisManager::class);

$redisManager->extend('custom', function (): void {
    assertType('Heritage\Redis\RedisManager', $this);
});
