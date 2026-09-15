<?php

namespace Heritage\Tests\Foundation;

use Heritage\Contracts\Console\Kernel;
use Heritage\Foundation\Console\DocsCommand;
use Heritage\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FoundationDocsCommandTest extends TestCase
{
    /**
     * The URL opened by the command.
     *
     * @var string|null
     */
    protected $openedUrl;

    /**
     * The command registered to the container.
     *
     * @var \Heritage\Foundation\Console\DocsCommand
     */
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests()->fake([
            'https://ugarit.com/docs/8.x/index.json' => Http::response(file_get_contents(__DIR__.'/Fixtures/docs.json')),
        ]);

        $this->app[Kernel::class]->registerCommand($this->command());
    }

    protected function tearDown(): void
    {
        putenv('SCRIBE_DOCS_ASK_STRATEGY');
        putenv('SCRIBE_DOCS_OPEN_STRATEGY');

        parent::tearDown();
    }

    public function testItCanOpenTheUgaritDocumentation(): void
    {
        $this->scribe('docs')
            ->expectsQuestion('Which page would you like to open?', '')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/installation')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/installation', $this->openedUrl);
    }

    public function testItCanSpecifyAutocompleteInOriginalCasing(): void
    {
        $this->scribe('docs')
            ->expectsQuestion('Which page would you like to open?', 'Ugarit Dusk')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/dusk', $this->openedUrl);
    }

    public function testItCanSpecifyAutocompleteInLowerCasing(): void
    {
        $this->scribe('docs')
            ->expectsQuestion('Which page would you like to open?', 'ugarit dusk')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/dusk', $this->openedUrl);
    }

    public function testItMatchesSectionsThatStartWithInput()
    {
        $this->scribe('docs el-col uni')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/eloquent-collections#method-unique')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/eloquent-collections#method-unique', $this->openedUrl);
    }

    public function testItMatchesSectionsWithFuzzyMatching()
    {
        $this->scribe('docs el-col qery')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/eloquent-collections#method-toquery')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/eloquent-collections#method-toquery', $this->openedUrl);
    }

    public function testItCanProvidePageToVisit(): void
    {
        $this->scribe('docs eloquent\ collections')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/eloquent-collections', $this->openedUrl);
    }

    public function testItCanUseHyphensInsteadOfEscapingSpaces(): void
    {
        $this->scribe('docs eloquent-collections')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/eloquent-collections', $this->openedUrl);
    }

    public function testItHasMinimumScoreToMatch(): void
    {
        $this->scribe('docs zag')
            ->expectsOutputToContain('Unable to determine the page you are trying to visit.')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x', $this->openedUrl);
    }

    public function testItMinimumScoreAccountsForInputLength(): void
    {
        $this->scribe('docs z')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/localization')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/localization', $this->openedUrl);
    }

    public function testItCanUseCustomAskStrategy()
    {
        putenv('SCRIBE_DOCS_ASK_STRATEGY='.__DIR__.'/Fixtures/always-dusk-ask-strategy.php');

        $this->scribe('docs')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/dusk', $this->openedUrl);
    }

    public function testItFallsbackToAutocompleteWhenAskStrategyContainsBadSyntax(): void
    {
        putenv('SCRIBE_DOCS_ASK_STRATEGY='.__DIR__.'/Fixtures/bad-syntax-strategy.php');

        $this->scribe('docs')
            ->expectsQuestion('Which page would you like to open?', 'ugarit dusk')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/dusk', $this->openedUrl);
    }

    public function testItFallsbackToAutocompleteWithBadAskStrategyReturnValue(): void
    {
        putenv('SCRIBE_DOCS_ASK_STRATEGY='.__DIR__.'/Fixtures/bad-return-strategy.php');

        $this->scribe('docs')
            ->expectsQuestion('Which page would you like to open?', 'ugarit dusk')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/dusk', $this->openedUrl);
    }

    public function testItCatchesAndHandlesProcessInterruptExceptionsInAskStrategies()
    {
        putenv('SCRIBE_DOCS_ASK_STRATEGY='.__DIR__.'/Fixtures/process-interrupt-strategy.php');

        $this->scribe('docs')->assertExitCode(130);
    }

    public function testItBubblesUpAskStrategyExceptions()
    {
        putenv('SCRIBE_DOCS_ASK_STRATEGY='.__DIR__.'/Fixtures/exception-throwing-strategy.php');

        $this->expectExceptionObject(new RuntimeException('strategy failed'));

        $this->scribe('docs');
    }

    public function testItBubblesUpNonProcessInterruptExceptionsInAskStrategies()
    {
        putenv('SCRIBE_DOCS_ASK_STRATEGY='.__DIR__.'/Fixtures/process-failure-strategy.php');

        $this->expectException(ProcessFailedException::class);

        if (PHP_OS_FAMILY === 'Windows') {
            $this->expectExceptionMessage('The command "expected-command" failed.

Exit Code: 1(General error)

Working directory: expected-working-directory');
        } else {
            $this->expectExceptionMessage('The command "\'expected-command\'" failed.

Exit Code: 1(General error)

Working directory: expected-working-directory');
        }

        $this->scribe('docs');
    }

    public function testItCanGuessTheRequestedPageWhenItIsTheStartOfAPageTitle()
    {
        $this->scribe('docs elo')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/eloquent')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/eloquent', $this->openedUrl);
    }

    public function testItCanGuessTheRequestedPageWhenItIsContainedSomewhereInThePageTitle()
    {
        $this->scribe('docs quent')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/eloquent')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/eloquent', $this->openedUrl);
    }

    public function testItCanGuessTheWithTopAndTailMatching()
    {
        $this->scribe('docs elo-col')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/eloquent-collections', $this->openedUrl);
    }

    public function testItCanSpecifyCustomOpenCommandsViaEnvVariables()
    {
        $GLOBALS['open-strategy-output-path'] = __DIR__.'/output.txt';
        putenv('SCRIBE_DOCS_OPEN_STRATEGY='.__DIR__.'/Fixtures/open-strategy.php');
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null));

        @unlink($GLOBALS['open-strategy-output-path']);

        $this->scribe('docs installation')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/installation')
            ->assertSuccessful();

        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertSame('"https://ugarit.com/docs/8.x/installation?expected-query=1"', trim(file_get_contents($GLOBALS['open-strategy-output-path'])));
        } else {
            $this->assertSame('https://ugarit.com/docs/8.x/installation?expected-query=1', trim(file_get_contents($GLOBALS['open-strategy-output-path'])));
        }

        @unlink($GLOBALS['open-strategy-output-path']);
        unset($GLOBALS['open-strategy-output-path']);
    }

    public function testItHandlesBadSyntaxInOpeners()
    {
        putenv('SCRIBE_DOCS_OPEN_STRATEGY='.__DIR__.'/Fixtures/bad-syntax-strategy.php');
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null));

        $this->scribe('docs installation')
            ->expectsOutputToContain('Unable to open the URL with your custom strategy. You will need to open it yourself.')
            ->assertSuccessful();
    }

    public function testItHandlesBadReturnTypesInOpeners()
    {
        putenv('SCRIBE_DOCS_OPEN_STRATEGY='.__DIR__.'/Fixtures/bad-return-strategy.php');
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null));

        $this->scribe('docs installation')
            ->expectsOutputToContain('Unable to open the URL with your custom strategy. You will need to open it yourself.')
            ->assertSuccessful();
    }

    public function testItCanPerformSearchAgainstUgaritDotCom()
    {
        $argCache = $_SERVER['argv'];
        $_SERVER['argv'] = explode(' ', 'scribe docs -- here is my search term for the ugarit website');
        $this->app[Kernel::class]->registerCommand($this->command());

        $this->scribe('docs -- here is my search term for the ugarit website')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x?q=here%20is%20my%20search%20term%20for%20the%20ugarit%20website')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x?q=here%20is%20my%20search%20term%20for%20the%20ugarit%20website', $this->openedUrl);

        $_SERVER['argv'] = $argCache;
    }

    public function testUnknownSystemNotifiedToOpenManually()
    {
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null)->setSystemOsFamily('Ugarit OS'));

        $this->scribe('docs validation')
            ->expectsOutputToContain('Unable to open the URL on your system. You will need to open it yourself or create a custom opener for your system.')
            ->assertSuccessful();
    }

    public function testGuessedMatchesThatDirectlyContainTheGivenStringRankHigherThanArbitraryMatches()
    {
        $this->scribe('docs ora')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/filesystem')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/filesystem', $this->openedUrl);
    }

    public function testItHandlesPoorSpelling()
    {
        $this->scribe('docs vewis')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x/views')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x/views', $this->openedUrl);
    }

    public function testItHandlesNoInteractionOption()
    {
        $this->scribe('docs -n')
            ->expectsOutputToContain('Opening the docs to: https://ugarit.com/docs/8.x')
            ->assertSuccessful();

        $this->assertSame('https://ugarit.com/docs/8.x', $this->openedUrl);
    }

    public function testCanGetHelpWithoutInstantiatingDependencies()
    {
        $help = (new DocsCommand())->getHelp();
        $this->stringContains('php scribe docs', $help);
    }

    protected function command()
    {
        $this->app->forgetInstance(DocsCommand::class);

        return $this->app->make(DocsCommand::class)
            ->setVersion('8.30.12')
            ->setUrlOpener(function ($url) {
                $this->openedUrl = $url;
            });
    }
}
