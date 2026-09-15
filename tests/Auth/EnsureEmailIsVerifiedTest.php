<?php

namespace Heritage\Tests\Auth;

use Heritage\Auth\Middleware\EnsureEmailIsVerified;
use PHPUnit\Framework\TestCase;

class EnsureEmailIsVerifiedTest extends TestCase
{
    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = EnsureEmailIsVerified::redirectTo('route.name');
        $this->assertSame('Heritage\Auth\Middleware\EnsureEmailIsVerified:route.name', $signature);
    }
}
