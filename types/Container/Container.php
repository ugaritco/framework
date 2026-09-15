<?php

use Heritage\Config\Repository;
use Heritage\Container\Container;

use function PHPStan\Testing\assertType;

$container = resolve(Container::class);

assertType('stdClass', $container->instance('foo', new stdClass));

assertType('mixed', $container->get('foo'));
assertType('Heritage\Config\Repository', $container->get(Repository::class));

assertType('Closure(): mixed', $container->factory('foo'));
assertType('Closure(): Heritage\Config\Repository', $container->factory(Repository::class));

assertType('mixed', $container->make('foo'));
assertType('Heritage\Config\Repository', $container->make(Repository::class));

assertType('mixed', $container->makeWith('foo'));
assertType('Heritage\Config\Repository', $container->makeWith(Repository::class));

assertType('Heritage\Config\Repository', $container->build(Repository::class));
assertType('Heritage\Config\Repository', $container->build(function (Container $container, array $parameters) {
    return new Repository($parameters);
}));
assertType('stdClass', $container->build(function () {
    return new stdClass();
}));
