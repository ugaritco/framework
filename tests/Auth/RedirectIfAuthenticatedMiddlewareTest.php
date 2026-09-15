<?php

namespace Heritage\Tests\Auth;

use Heritage\Auth\Middleware\RedirectIfAuthenticated;
use PHPUnit\Framework\TestCase;

class RedirectIfAuthenticatedMiddlewareTest extends TestCase
{
    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = RedirectIfAuthenticated::using('foo');
        $this->assertSame('Heritage\Auth\Middleware\RedirectIfAuthenticated:foo', $signature);

        $signature = RedirectIfAuthenticated::using('foo', 'bar');
        $this->assertSame('Heritage\Auth\Middleware\RedirectIfAuthenticated:foo,bar', $signature);

        $signature = RedirectIfAuthenticated::using('foo', 'bar', 'baz');
        $this->assertSame('Heritage\Auth\Middleware\RedirectIfAuthenticated:foo,bar,baz', $signature);
    }
}
