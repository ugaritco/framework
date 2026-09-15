<?php

use Heritage\Support\Stringable;

use function PHPStan\Testing\assertType;

$stringable = new Stringable();

assertType('Heritage\Support\Collection<int, string>', $stringable->explode(''));

assertType('Heritage\Support\Collection<int, string>', $stringable->split(1));

assertType('Heritage\Support\Collection<int, string>', $stringable->ucsplit());
