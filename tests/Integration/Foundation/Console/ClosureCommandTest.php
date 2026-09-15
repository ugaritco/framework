<?php

namespace Heritage\Tests\Integration\Foundation\Console;

use Heritage\Support\Facades\Scribe;
use Orchestra\Testbench\TestCase;

class ClosureCommandTest extends TestCase
{
    /** {@inheritDoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        Scribe::command('inspire', function () {
            $this->comment('We must ship. - Taylor Otwell');
        })->purpose('Display an inspiring quote');
    }

    public function testItCanRunClosureCommand()
    {
        $this->scribe('inspire')->expectsOutput('We must ship. - Taylor Otwell');
    }
}
