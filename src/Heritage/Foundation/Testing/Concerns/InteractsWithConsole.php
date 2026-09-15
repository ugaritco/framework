<?php

namespace Heritage\Foundation\Testing\Concerns;

use Heritage\Console\OutputStyle;
use Heritage\Contracts\Console\Kernel;
use Heritage\Testing\PendingCommand;

trait InteractsWithConsole
{
    /**
     * Indicates if the console output should be mocked.
     *
     * @var bool
     */
    public $mockConsoleOutput = true;

    /**
     * Indicates if the command is expected to output anything.
     *
     * @var bool|null
     */
    public $expectsOutput;

    /**
     * All of the expected output lines.
     *
     * @var array
     */
    public $expectedOutput = [];

    /**
     * All of the expected text to be present in the output.
     *
     * @var array
     */
    public $expectedOutputSubstrings = [];

    /**
     * All of the output lines that aren't expected to be displayed.
     *
     * @var array
     */
    public $unexpectedOutput = [];

    /**
     * All of the text that is not expected to be present in the output.
     *
     * @var array
     */
    public $unexpectedOutputSubstrings = [];

    /**
     * All of the expected output tables.
     *
     * @var array
     */
    public $expectedTables = [];

    /**
     * All of the expected questions.
     *
     * @var array
     */
    public $expectedQuestions = [];

    /**
     * All of the expected choice questions.
     *
     * @var array
     */
    public $expectedChoices = [];

    /**
     * Call scribe command and return code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Heritage\Testing\PendingCommand|int
     */
    public function scribe($command, $parameters = [])
    {
        if (! $this->mockConsoleOutput) {
            return $this->app[Kernel::class]->call($command, $parameters);
        }

        return new PendingCommand($this, $this->app, $command, $parameters);
    }

    /**
     * Disable mocking the console output.
     *
     * @return $this
     */
    protected function withoutMockingConsoleOutput()
    {
        $this->mockConsoleOutput = false;

        $this->app->offsetUnset(OutputStyle::class);

        return $this;
    }
}
