<?php

use Heritage\Config\Repository;
use Heritage\Contracts\Foundation\Application;

use function PHPStan\Testing\assertType;

$app = resolve(Application::class);

assertType('stdClass', $app->instance('foo', new stdClass));

assertType('mixed', $app->get('foo'));
assertType('Heritage\Config\Repository', $app->get(Repository::class));

assertType('Closure(): mixed', $app->factory('foo'));
assertType('Closure(): Heritage\Config\Repository', $app->factory(Repository::class));

assertType('mixed', $app->make('foo'));
assertType('Heritage\Config\Repository', $app->make(Repository::class));
