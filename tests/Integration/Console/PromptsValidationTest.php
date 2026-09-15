<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Console\Command;
use Heritage\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;

use function Ugarit\Prompts\text;

class PromptsValidationTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app[Kernel::class]->registerCommand(new DummyPromptsValidationCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsWithUgaritRulesCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsWithUgaritRulesMessagesAndAttributesCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsWithUgaritRulesCommandWithInlineMessagesAndAttributesCommand());
    }

    public function testValidationForPrompts(): void
    {
        $this
            ->scribe(DummyPromptsValidationCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('Required!');
    }

    public function testValidationWithUgaritRulesAndNoCustomization(): void
    {
        $this
            ->scribe(DummyPromptsWithUgaritRulesCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('The answer field is required.');
    }

    public function testValidationWithUgaritRulesInlineMessagesAndAttributes(): void
    {
        $this
            ->scribe(DummyPromptsWithUgaritRulesCommandWithInlineMessagesAndAttributesCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('Your full name is mandatory.');
    }

    public function testValidationWithUgaritRulesMessagesAndAttributes(): void
    {
        $this
            ->scribe(DummyPromptsWithUgaritRulesMessagesAndAttributesCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('Your full name is mandatory.');
    }
}

class DummyPromptsValidationCommand extends Command
{
    protected $signature = 'prompts-validation-test';

    public function handle()
    {
        text('What is your name?', validate: fn ($value) => $value == '' ? 'Required!' : null);
    }
}

class DummyPromptsWithUgaritRulesCommand extends Command
{
    protected $signature = 'prompts-ugarit-rules-test';

    public function handle()
    {
        text('What is your name?', validate: 'required');
    }
}

class DummyPromptsWithUgaritRulesCommandWithInlineMessagesAndAttributesCommand extends Command
{
    protected $signature = 'prompts-ugarit-rules-inline-test';

    public function handle()
    {
        text('What is your name?', validate: literal(
            rules: ['name' => 'required'],
            messages: ['name.required' => 'Your :attribute is mandatory.'],
            attributes: ['name' => 'full name'],
        ));
    }
}

class DummyPromptsWithUgaritRulesMessagesAndAttributesCommand extends Command
{
    protected $signature = 'prompts-ugarit-rules-messages-attributes-test';

    public function handle()
    {
        text('What is your name?', validate: ['name' => 'required']);
    }

    protected function validationMessages()
    {
        return ['name.required' => 'Your :attribute is mandatory.'];
    }

    protected function validationAttributes()
    {
        return ['name' => 'full name'];
    }
}
