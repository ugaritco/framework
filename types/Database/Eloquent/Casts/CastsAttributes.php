<?php

use function PHPStan\Testing\assertType;

/** @var User $user */
/** @var \Heritage\Contracts\Database\Eloquent\CastsAttributes<\Heritage\Support\Stringable, string|\Stringable> $cast */
assertType('Heritage\Support\Stringable|null', $cast->get($user, 'email', 'taylor@ugarit.com', $user->getAttributes()));

$cast->set($user, 'email', 'taylor@ugarit.com', $user->getAttributes()); // This works.
$cast->set($user, 'email', \Heritage\Support\Str::of('taylor@ugarit.com'), $user->getAttributes()); // This also works!
$cast->set($user, 'email', null, $user->getAttributes()); // Also valid.
