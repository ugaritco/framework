<?php

use Heritage\Config\Repository;
use Heritage\Contracts\Container\Container;
use Heritage\Http\Request;

use function PHPStan\Testing\assertType;

$container = resolve(Container::class);

assertType('stdClass', $container->instance('foo', new stdClass));

assertType('mixed', $container->get('foo'));
assertType('Heritage\Config\Repository', $container->get(Repository::class));

assertType('Closure(): mixed', $container->factory('foo'));
assertType('Closure(): Heritage\Config\Repository', $container->factory(Repository::class));

assertType('mixed', $container->make('foo'));
assertType('Heritage\Config\Repository', $container->make(Repository::class));

assertType('Heritage\Http\Request', $container->instance('request', Request::capture()));
