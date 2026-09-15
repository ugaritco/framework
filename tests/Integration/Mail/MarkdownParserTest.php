<?php

namespace Heritage\Tests\Integration\Mail;

use Heritage\Mail\Markdown;
use Heritage\Support\EncodedHtmlString;
use Heritage\Support\HtmlString;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MarkdownParserTest extends TestCase
{
    protected function tearDown(): void
    {
        Markdown::flushState();
        EncodedHtmlString::flushState();

        parent::tearDown();
    }

    #[DataProvider('markdownDataProvider')]
    public function testItCanParseMarkdownString($given, $expected)
    {
        tap(Markdown::parse($given), function ($html) use ($expected) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
            $this->assertSame((string) $html, (string) $html->toHtml());
        });
    }

    #[DataProvider('markdownEncodedDataProvider')]
    public function testItCanParseMarkdownEncodedString($given, $expected)
    {
        tap(Markdown::parse($given, encoded: true), function ($html) use ($expected) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
        });
    }

    public static function markdownDataProvider()
    {
        yield ['[Ugarit](https://ugarit.com)', '<p><a href="https://ugarit.com">Ugarit</a></p>'];
        yield ['\[Ugarit](https://ugarit.com)', '<p>[Ugarit](https://ugarit.com)</p>'];
        yield ['![Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)', '<p><img src="https://ugarit.com/assets/img/welcome/background.svg" alt="Welcome to Ugarit" /></p>'];
        yield ['!\[Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)', '<p>![Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)</p>'];
        yield ['Visit https://ugarit.com/docs to browse the documentation', '<p>Visit https://ugarit.com/docs to browse the documentation</p>'];
        yield ['Visit <https://ugarit.com/docs> to browse the documentation', '<p>Visit <a href="https://ugarit.com/docs">https://ugarit.com/docs</a> to browse the documentation</p>'];
        yield ['Visit <span>https://ugarit.com/docs</span> to browse the documentation', '<p>Visit <span>https://ugarit.com/docs</span> to browse the documentation</p>'];
    }

    public static function markdownEncodedDataProvider()
    {
        yield [new EncodedHtmlString('[Ugarit](https://ugarit.com)'), '<p>[Ugarit](https://ugarit.com)</p>'];

        yield [
            new EncodedHtmlString('![Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)'),
            '<p>![Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)</p>',
        ];

        yield [
            new EncodedHtmlString('Visit https://ugarit.com/docs to browse the documentation'),
            '<p>Visit https://ugarit.com/docs to browse the documentation</p>',
        ];

        yield [
            new EncodedHtmlString('Visit <https://ugarit.com/docs> to browse the documentation'),
            '<p>Visit &lt;https://ugarit.com/docs&gt; to browse the documentation</p>',
        ];

        yield [
            new EncodedHtmlString('Visit <span>https://ugarit.com/docs</span> to browse the documentation'),
            '<p>Visit &lt;span&gt;https://ugarit.com/docs&lt;/span&gt; to browse the documentation</p>',
        ];

        yield [
            new EncodedHtmlString(new HtmlString('Visit <span>https://ugarit.com/docs</span> to browse the documentation')),
            '<p>Visit <span>https://ugarit.com/docs</span> to browse the documentation</p>',
        ];

        yield [
            '![Welcome to Ugarit](https://ugarit.com/assets/img/welcome/background.svg)<br />'.new EncodedHtmlString('Visit <span>https://ugarit.com/docs</span> to browse the documentation'),
            '<p><img src="https://ugarit.com/assets/img/welcome/background.svg" alt="Welcome to Ugarit" /><br />Visit &lt;span&gt;https://ugarit.com/docs&lt;/span&gt; to browse the documentation</p>',
        ];
    }

    public function testItCanParseMarkdownWithCustomExtensionsViaConfig(): void
    {
        $this->configureMarkdownExtensions([
            \League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
        ]);

        tap(Markdown::parse('~~strikethrough text~~'), function ($html) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $expected = '<p><del>strikethrough text</del></p>';

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
            $this->assertSame((string) $html, (string) $html->toHtml());
        });
    }

    public function testItCanParseMarkdownWithoutCustomExtensionsDoesNotApplyThem(): void
    {
        $this->configureMarkdownExtensions([]);

        tap(Markdown::parse('~~strikethrough text~~'), function ($html) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $expected = '<p>~~strikethrough text~~</p>';

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
            $this->assertSame((string) $html, (string) $html->toHtml());
        });
    }

    public function testItCanParseMarkdownWithMultipleCustomExtensions(): void
    {
        $this->configureMarkdownExtensions([
            \League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
            \League\CommonMark\Extension\TaskList\TaskListExtension::class,
        ]);

        tap(Markdown::parse('~~strikethrough~~'), function ($html) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $expected = '<p><del>strikethrough</del></p>';

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
            $this->assertSame((string) $html, (string) $html->toHtml());
        });

        tap(Markdown::parse('- [ ] Task item'), function ($html) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $expected = "<ul>\n<li><input disabled=\"\" type=\"checkbox\"> Task item</li>\n</ul>";

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
            $this->assertSame((string) $html, (string) $html->toHtml());
        });
    }

    public function testItCanParseMarkdownEncodedStringWithCustomExtensions(): void
    {
        $this->configureMarkdownExtensions([
            \League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
        ]);

        tap(Markdown::parse(new EncodedHtmlString('~~strikethrough text~~'), encoded: true), function ($html) {
            $this->assertInstanceOf(HtmlString::class, $html);

            $expected = '<p><del>strikethrough text</del></p>';

            $this->assertStringEqualsStringIgnoringLineEndings($expected.PHP_EOL, (string) $html);
        });
    }

    /**
     * @param  array<int, class-string<\League\CommonMark\Extension\ExtensionInterface>>  $extensions
     */
    protected function configureMarkdownExtensions(array $extensions): void
    {
        $this->app['config']->set('mail.markdown.extensions', $extensions);

        $this->app->forgetInstance(Markdown::class);
        $this->app->make(Markdown::class);
    }
}
