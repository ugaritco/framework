<?php

namespace Heritage\Tests\Integration\Support;

use Heritage\Support\Facades\Mail;
use Heritage\Tests\Mail\Fixtures\TestMail;
use Orchestra\Testbench\TestCase;

class SupportMailTest extends TestCase
{
    public function testItRegisterAndCallMacros()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        $this->assertSame('it works!', Mail::test('foo'));
    }

    public function testItRegisterAndCallMacrosWhenFaked()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        Mail::fake();

        $this->assertSame('it works!', Mail::test('foo'));
    }

    public function testEmailSent()
    {
        Mail::fake();
        Mail::assertNothingSent();

        Mail::to('hello@ugarit.com')->send(new TestMail());

        Mail::assertSent(TestMail::class);
    }
}
