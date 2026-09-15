<?php

declare(strict_types=1);

use Heritage\Cache\CacheManager;

use function PHPStan\Testing\assertType;

$cacheManager = resolve(CacheManager::class);

$cacheManager->extend('redis', function (): void {
    assertType('Heritage\Cache\CacheManager', $this);
});
