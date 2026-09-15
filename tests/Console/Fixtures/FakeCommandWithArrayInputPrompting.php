<?php

namespace Heritage\Tests\Console\Fixtures;

use Heritage\Console\Command;
use Heritage\Contracts\Console\PromptsForMissingInput;
use Ugarit\Prompts\Prompt;
use Ugarit\Prompts\TextPrompt;
use Symfony\Component\Console\Input\InputInterface;

class FakeCommandWithArrayInputPrompting extends Command implements PromptsForMissingInput
{
    protected $signature = 'fake-command-for-testing-array {names* : An array argument}';

    public $prompted = false;

    protected function configurePrompts(InputInterface $input)
    {
        Prompt::interactive(true);
        Prompt::fallbackWhen(true);

        TextPrompt::fallbackUsing(function () {
            $this->prompted = true;

            return 'foo';
        });
    }

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
