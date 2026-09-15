<?php

namespace Heritage\Tests\Mail;

use Heritage\Mail\Mailable;
use Heritage\Mail\Mailables\Address;
use Heritage\Mail\Mailables\Content;
use Heritage\Mail\Mailables\Envelope;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MailableAlternativeSyntaxTest extends TestCase
{
    public function testBasicMailableInspection(): void
    {
        $mailable = new MailableWithAlternativeSyntax;

        $this->assertTrue($mailable->hasTo('taylor@ugarit.com'));
        $this->assertTrue($mailable->hasCc('adam@ugarit.com'));
        $this->assertTrue($mailable->hasBcc('tyler@ugarit.com'));
        $this->assertTrue($mailable->hasTo('taylor@ugarit.com', 'Taylor Otwell'));
        $this->assertFalse($mailable->hasTo('taylor@ugarit.com', 'Wrong Name'));

        $mailable->to(new Address('abigail@ugarit.com', 'Abigail Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@ugarit.com', 'Taylor Otwell'));

        $this->assertTrue($mailable->hasSubject('Test Subject'));
        $this->assertFalse($mailable->hasSubject('Wrong Subject'));
        $this->assertTrue($mailable->hasTag('tag-1'));
        $this->assertTrue($mailable->hasMetadata('test-meta', 'test-meta-value'));

        $reflection = new ReflectionClass($mailable);
        $method = $reflection->getMethod('prepareMailableForDelivery');
        $method->invoke($mailable);

        $this->assertSame('test-view', $mailable->view);
        $this->assertEquals(['test-data-key' => 'test-data-value'], $mailable->viewData);
        $this->assertCount(2, $mailable->to);
        $this->assertCount(1, $mailable->cc);
        $this->assertCount(1, $mailable->bcc);
    }

    public function testEnvelopesCanReceiveAdditionalRecipients(): void
    {
        $envelope = new Envelope(to: ['taylor@example.com']);
        $envelope->to(new Address('taylorotwell@example.com'));

        $this->assertCount(2, $envelope->to);
        $this->assertSame('taylor@example.com', $envelope->to[0]->address);
        $this->assertSame('taylorotwell@example.com', $envelope->to[1]->address);

        $envelope->to('abigailotwell@example.com', 'Abigail Otwell');
        $this->assertSame('abigailotwell@example.com', $envelope->to[2]->address);
        $this->assertSame('Abigail Otwell', $envelope->to[2]->name);

        $envelope->to('adam@example.com');
        $this->assertSame('adam@example.com', $envelope->to[3]->address);
        $this->assertNull($envelope->to[3]->name);

        $envelope->to(['jeffrey@example.com', 'tyler@example.com']);
        $this->assertSame('jeffrey@example.com', $envelope->to[4]->address);
        $this->assertSame('tyler@example.com', $envelope->to[5]->address);

        $envelope->from('dries@example.com', 'Dries Vints');
        $this->assertSame('dries@example.com', $envelope->from->address);
        $this->assertSame('Dries Vints', $envelope->from->name);
    }
}

class MailableWithAlternativeSyntax extends Mailable
{
    public function envelope()
    {
        return new Envelope(
            to: [new Address('taylor@ugarit.com', 'Taylor Otwell')],
            cc: [new Address('adam@ugarit.com', 'Adam Wathan')],
            bcc: [new Address('tyler@ugarit.com', 'Tyler Blair')],
            subject: 'Test Subject',
            tags: ['tag-1', 'tag-2'],
            metadata: ['test-meta' => 'test-meta-value'],
        );
    }

    public function content()
    {
        return new Content(
            view: 'test-view',
            with: ['test-data-key' => 'test-data-value'],
        );
    }
}
