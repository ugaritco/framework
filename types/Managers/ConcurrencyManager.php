<?php

declare(strict_types=1);

use Heritage\Concurrency\ConcurrencyManager;

use function PHPStan\Testing\assertType;

$concurrencyManager = resolve(ConcurrencyManager::class);

$concurrencyManager->extend('custom', function (): void {
    assertType('Heritage\Concurrency\ConcurrencyManager', $this);
});
