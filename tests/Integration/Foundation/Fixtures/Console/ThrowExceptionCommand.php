<?php

namespace Heritage\Tests\Integration\Foundation\Fixtures\Console;

use Exception;
use Heritage\Console\Command;

class ThrowExceptionCommand extends Command
{
    protected $signature = 'throw-exception-command';

    public function handle()
    {
        throw new Exception('Thrown inside ThrowExceptionCommand');
    }
}
