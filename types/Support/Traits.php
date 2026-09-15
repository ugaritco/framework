<?php

use Heritage\Support\Traits\Localizable;
use Heritage\Support\UriQueryString;

use function PHPStan\Testing\assertType;

$localizable = new class
{
    use Localizable;

    public function useWithLocale(): void
    {
        assertType("'foo'", $this->withLocale('en', fn () => 'foo'));
    }
};

$interactsWithData = function (UriQueryString $query): void {
    assertType('1|2|Heritage\Support\UriQueryString', $query->whenEnum('foo', TestIntEnum::class, function ($enum) {
        assertType('TestIntEnum', $enum);

        return 1;
    }, function () {
        return 2;
    }));

    assertType('3|Heritage\Support\UriQueryString', $query->whenEnum('foo', TestIntEnum::class, function ($enum) {
        return 3;
    }));

    assertType('1|2|Heritage\Support\UriQueryString', $query->whenHas('foo', function ($value) {
        assertType('mixed', $value);

        return 1;
    }, function () {
        return 2;
    }));

    assertType('3|Heritage\Support\UriQueryString', $query->whenHas('foo', function ($value) {
        return 3;
    }));

    assertType('1|2|Heritage\Support\UriQueryString', $query->whenFilled('foo', function ($value) {
        assertType('mixed', $value);

        return 1;
    }, function () {
        return 2;
    }));

    assertType('3|Heritage\Support\UriQueryString', $query->whenFilled('foo', function ($value) {
        return 3;
    }));

    assertType('1|2|Heritage\Support\UriQueryString', $query->whenMissing('foo', function ($value) {
        assertType('mixed', $value);

        return 1;
    }, function () {
        return 2;
    }));

    assertType('3|Heritage\Support\UriQueryString', $query->whenMissing('foo', function ($value) {
        return 3;
    }));
};

enum TestIntEnum: int
{
}
