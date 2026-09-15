<?php

use function PHPStan\Testing\assertType;

assertType(
    'Heritage\Contracts\Database\Eloquent\CastsAttributes<Heritage\Database\Eloquent\Casts\ArrayObject<(int|string), mixed>, iterable>',
    \Heritage\Database\Eloquent\Casts\AsArrayObject::castUsing([]),
);

assertType(
    'Heritage\Contracts\Database\Eloquent\CastsAttributes<Heritage\Support\Collection<(int|string), mixed>, iterable>',
    \Heritage\Database\Eloquent\Casts\AsCollection::castUsing([]),
);

assertType(
    'Heritage\Contracts\Database\Eloquent\CastsAttributes<Heritage\Database\Eloquent\Casts\ArrayObject<(int|string), mixed>, iterable>',
    \Heritage\Database\Eloquent\Casts\AsEncryptedArrayObject::castUsing([]),
);

assertType(
    'Heritage\Contracts\Database\Eloquent\CastsAttributes<Heritage\Support\Collection<(int|string), mixed>, iterable>',
    \Heritage\Database\Eloquent\Casts\AsEncryptedCollection::castUsing([]),
);

assertType(
    'Heritage\Contracts\Database\Eloquent\CastsAttributes<Heritage\Database\Eloquent\Casts\ArrayObject<(int|string), UserType>, iterable<UserType>>',
    \Heritage\Database\Eloquent\Casts\AsEnumArrayObject::castUsing([\UserType::class]),
);

assertType(
    'Heritage\Contracts\Database\Eloquent\CastsAttributes<Heritage\Support\Collection<(int|string), UserType>, iterable<UserType>>',
    \Heritage\Database\Eloquent\Casts\AsEnumCollection::castUsing([\UserType::class]),
);

assertType(
    'Heritage\Contracts\Database\Eloquent\CastsAttributes<Heritage\Support\Stringable, string|Stringable>',
    \Heritage\Database\Eloquent\Casts\AsStringable::castUsing([]),
);
