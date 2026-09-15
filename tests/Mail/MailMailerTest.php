<?php

namespace Heritage\Tests\Mail;

use Heritage\Contracts\Events\Dispatcher;
use Heritage\Contracts\View\Factory;
use Heritage\Mail\Events\MessageSending;
use Heritage\Mail\Events\MessageSent;
use Heritage\Mail\Mailer;
use Heritage\Mail\Message;
use Heritage\Mail\Transport\ArrayTransport;
use Heritage\Support\HtmlString;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;

class MailMailerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['__mailer.test']);
    }

    public function testMailerSendSendsMessageWithProperViewContent(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
        });

        $this->assertStringContainsString('rendered.view', $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithCcAndBccRecipients(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@ugarit.com')
                ->cc('dries@ugarit.com')
                ->bcc('james@ugarit.com')
                ->from('hello@ugarit.com');
        });

        $recipients = collect($sentMessage->getEnvelope()->getRecipients())->map(function ($recipient) {
            return $recipient->getAddress();
        });

        $this->assertStringContainsString('rendered.view', $sentMessage->toString());
        $this->assertStringContainsString('dries@ugarit.com', $sentMessage->toString());
        $this->assertStringNotContainsString('james@ugarit.com', $sentMessage->toString());
        $this->assertTrue($recipients->contains('james@ugarit.com'));
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlStrings(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->shouldReceive('render')->never();

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(
            ['html' => new HtmlString('<p>Hello Ugarit</p>'), 'text' => new HtmlString('Hello World')],
            ['data'],
            function (Message $message) {
                $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
            }
        );

        $this->assertStringContainsString('<p>Hello Ugarit</p>', $sentMessage->toString());
        $this->assertStringContainsString('Hello World', $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingStringCallbacks(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->shouldReceive('render')->never();

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(
            [
                'html' => function ($data) {
                    $this->assertInstanceOf(Message::class, $data['message']);

                    return new HtmlString('<p>Hello Ugarit</p>');
                },
                'text' => function ($data) {
                    $this->assertInstanceOf(Message::class, $data['message']);

                    return new HtmlString('Hello World');
                },
            ],
            [],
            function (Message $message) {
                $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
            }
        );

        $this->assertStringContainsString('<p>Hello Ugarit</p>', $sentMessage->toString());
        $this->assertStringContainsString('Hello World', $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperViewContentUsingHtmlMethod(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->shouldReceive('render')->never();

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->html('<p>Hello World</p>', function (Message $message) {
            $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
        });

        $this->assertStringContainsString('<p>Hello World</p>', $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperPlainViewContent(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->times(2)->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $view->expects('render')->andReturn('rendered.plain');

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(['foo', 'bar'], ['data'], function (Message $message) {
            $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
        });

        $expected = <<<Text
        Content-Type: text/html; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.view
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());

        $expected = <<<Text
        Content-Type: text/plain; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.plain
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());
    }

    public function testMailerSendSendsMessageWithProperPlainViewContentWhenExplicit(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->times(2)->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $view->expects('render')->andReturn('rendered.plain');

        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(['html' => 'foo', 'text' => 'bar'], ['data'], function (Message $message) {
            $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
        });

        $expected = <<<Text
        Content-Type: text/html; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.view
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());

        $expected = <<<Text
        Content-Type: text/plain; charset=utf-8\r
        Content-Transfer-Encoding: quoted-printable\r
        \r
        rendered.plain
        Text;

        $this->assertStringContainsString($expected, $sentMessage->toString());
    }

    public function testToAllowsEmailAndName(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $mailer = new Mailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->to('taylor@ugarit.com', 'Taylor Otwell')->send(new TestMail());

        $recipients = $sentMessage->getEnvelope()->getRecipients();
        $this->assertCount(1, $recipients);
        $this->assertSame('taylor@ugarit.com', $recipients[0]->getAddress());
        $this->assertSame('Taylor Otwell', $recipients[0]->getName());
    }

    public function testMailerRejectsAddressesContainingLineBreaks(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $mailer = new Mailer('array', $view, new ArrayTransport);

        $this->expectExceptionObject(new InvalidArgumentException('Email addresses may not contain line break characters.'));

        $mailer->send('foo', ['data'], function (Message $message) {
            $message->to("\"foo\r\nBcc: victim@example.com\"@example.com")->from('hello@ugarit.com');
        });
    }

    public function testMailerRejectsSymfonyAddressesContainingLineBreaks(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $mailer = new Mailer('array', $view, new ArrayTransport);

        try {
            $mailer->send('foo', ['data'], function (Message $message) {
                $message->to(new Address("\"foo\r\nBcc: victim@example.com\"@example.com"))->from('hello@ugarit.com');
            });

            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertContains($e->getMessage(), [
                'Email address contains control characters.',
                'Email addresses may not contain line break characters.',
            ]);
        }
    }

    public function testGlobalFromIsRespectedOnAllMessages(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $mailer = new Mailer('array', $view, new ArrayTransport);
        $mailer->alwaysFrom('hello@ugarit.com');

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@ugarit.com');
        });

        $this->assertSame('taylor@ugarit.com', $sentMessage->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertSame('hello@ugarit.com', $sentMessage->getEnvelope()->getSender()->getAddress());
    }

    public function testGlobalReplyToIsRespectedOnAllMessages(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $mailer = new Mailer('array', $view, new ArrayTransport);
        $mailer->alwaysReplyTo('taylor@ugarit.com', 'Taylor Otwell');

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('dries@ugarit.com')->from('hello@ugarit.com');
        });

        $this->assertSame('dries@ugarit.com', $sentMessage->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertStringContainsString('Reply-To: Taylor Otwell <taylor@ugarit.com>', $sentMessage->toString());
    }

    public function testGlobalToIsRespectedOnAllMessages(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');
        $mailer = new Mailer('array', $view, new ArrayTransport);
        $mailer->alwaysTo('taylor@ugarit.com', 'Taylor Otwell');

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->from('hello@ugarit.com');
            $message->to('nuno@ugarit.com');
            $message->cc('dries@ugarit.com');
            $message->bcc('james@ugarit.com');
        });

        $recipients = collect($sentMessage->getEnvelope()->getRecipients())->map(function ($recipient) {
            return $recipient->getAddress();
        });

        $this->assertSame('taylor@ugarit.com', $sentMessage->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertDoesNotMatchRegularExpression('/^To: nuno@ugarit.com/m', $sentMessage->toString());
        $this->assertDoesNotMatchRegularExpression('/^Cc: dries@ugarit.com/m', $sentMessage->toString());
        $this->assertMatchesRegularExpression('/^X-To: nuno@ugarit.com/m', $sentMessage->toString());
        $this->assertMatchesRegularExpression('/^X-Cc: dries@ugarit.com/m', $sentMessage->toString());
        $this->assertMatchesRegularExpression('/^X-Bcc: james@ugarit.com/m', $sentMessage->toString());
        $this->assertFalse($recipients->contains('nuno@ugarit.com'));
        $this->assertFalse($recipients->contains('dries@ugarit.com'));
        $this->assertFalse($recipients->contains('james@ugarit.com'));
    }

    public function testGlobalReturnPathIsRespectedOnAllMessages(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');

        $mailer = new Mailer('array', $view, new ArrayTransport);
        $mailer->alwaysReturnPath('taylorotwell@gmail.com');

        $sentMessage = $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
        });

        $this->assertStringContainsString('Return-Path: <taylorotwell@gmail.com>', $sentMessage->toString());
    }

    public function testEventsAreDispatched(): void
    {
        $view = Mockery::mock(Factory::class);
        $view->expects('make')->andReturn($view);
        $view->expects('render')->andReturn('rendered.view');

        $events = Mockery::mock(Dispatcher::class);
        $events->expects('until')->with(Mockery::type(MessageSending::class));
        $events->expects('dispatch')->with(Mockery::type(MessageSent::class));

        $mailer = new Mailer('array', $view, new ArrayTransport, $events);

        $mailer->send('foo', ['data'], function (Message $message) {
            $message->to('taylor@ugarit.com')->from('hello@ugarit.com');
        });
    }

    public function testMacroable(): void
    {
        Mailer::macro('foo', function () {
            return 'bar';
        });

        $mailer = new Mailer('array', Mockery::mock(Factory::class), new ArrayTransport);

        $this->assertSame(
            'bar', $mailer->foo()
        );
    }
}

class TestMail extends \Heritage\Mail\Mailable
{
    public function build()
    {
        return $this->view('view')
            ->from('hello@ugarit.com');
    }
}
