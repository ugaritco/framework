<?php

namespace Heritage\Tests\Integration\Mail;

use Heritage\Mail\Mailable;
use Heritage\Mail\Mailables\Content;
use Heritage\Mail\Mailables\Envelope;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class MailableTestCase extends TestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $app['view']->addLocation(__DIR__.'/Fixtures');
    }

    #[DataProvider('markdownEncodedDataProvider')]
    public function testItCanAssertMarkdownEncodedString($given, $expected)
    {
        $mailable = new class($given) extends Mailable
        {
            public function __construct(public string $message)
            {
                //
            }

            public function envelope()
            {
                return new Envelope(
                    subject: 'My basic title',
                );
            }

            public function content()
            {
                return new Content(
                    markdown: 'message',
                );
            }
        };

        $mailable->assertSeeInHtml($expected, false);
    }

    public static function markdownEncodedDataProvider()
    {
        yield ['[Ugarit](https://ugarit.com)', 'My message is: [Ugarit](https://ugarit.com)'];

        yield [
            '![Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)',
            'My message is: ![Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)',
        ];

        yield [
            'Visit https://ugarit.com/docs to browse the documentation',
            'My message is: Visit https://ugarit.com/docs to browse the documentation',
        ];

        yield [
            'Visit <https://ugarit.com/docs> to browse the documentation',
            'My message is: Visit &lt;https://ugarit.com/docs&gt; to browse the documentation',
        ];

        yield [
            'Visit <span>https://ugarit.com/docs</span> to browse the documentation',
            'My message is: Visit &lt;span&gt;https://ugarit.com/docs&lt;/span&gt; to browse the documentation',
        ];
    }
}
