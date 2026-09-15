<?php

namespace Heritage\Tests\Integration\Console;

use Heritage\Console\Command;
use Heritage\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;

use function Ugarit\Prompts\confirm;
use function Ugarit\Prompts\multisearch;
use function Ugarit\Prompts\multiselect;
use function Ugarit\Prompts\number;
use function Ugarit\Prompts\password;
use function Ugarit\Prompts\pause;
use function Ugarit\Prompts\search;
use function Ugarit\Prompts\select;
use function Ugarit\Prompts\suggest;
use function Ugarit\Prompts\text;
use function Ugarit\Prompts\textarea;

class PromptsAssertionTest extends TestCase
{
    public function testAssertionForTextPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:text';

                public function handle()
                {
                    $name = text('What is your name?', 'John');

                    $this->line($name);
                }
            }
        );

        $this
            ->scribe('test:text')
            ->expectsQuestion('What is your name?', 'Jane')
            ->expectsOutput('Jane');
    }

    public function testAssertionForPausePrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class($this) extends Command
            {
                protected $signature = 'test:pause';

                public function __construct(public PromptsAssertionTest $test)
                {
                    parent::__construct();
                }

                public function handle()
                {
                    $value = pause('Press any key to continue...');
                    $this->test->assertEquals(true, $value);
                }
            }
        );

        $this
            ->scribe('test:pause')
            ->expectsQuestion('Press any key to continue...', '');
    }

    public function testAssertionForTextareaPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:textarea';

                public function handle()
                {
                    $name = textarea('What is your name?', 'John');

                    $this->line($name);
                }
            }
        );

        $this
            ->scribe('test:textarea')
            ->expectsQuestion('What is your name?', 'Jane')
            ->expectsOutput('Jane');
    }

    public function testAssertionForNumberPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:number';

                public function handle()
                {
                    $count = number('How many people?');

                    $this->line("There are {$count} people.");
                }
            }
        );

        $this
            ->scribe('test:number')
            ->expectsQuestion('How many people?', 5)
            ->expectsOutput('There are 5 people.');
    }

    public function testAssertionForSuggestPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:suggest';

                public function handle()
                {
                    $name = suggest('What is your name?', ['John', 'Jane']);

                    $this->line($name);
                }
            }
        );

        $this
            ->scribe('test:suggest')
            ->expectsChoice('What is your name?', 'Joe', ['John', 'Jane'])
            ->expectsOutput('Joe');
    }

    public function testAssertionForPasswordPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:password';

                public function handle()
                {
                    $name = password('What is your password?');

                    $this->line($name);
                }
            }
        );

        $this
            ->scribe('test:password')
            ->expectsQuestion('What is your password?', 'secret')
            ->expectsOutput('secret');
    }

    public function testAssertionForConfirmPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:confirm';

                public function handle()
                {
                    $confirmed = confirm('Is your name John?');

                    if ($confirmed) {
                        $this->line('Your name is John.');
                    } else {
                        $this->line('Your name is not John.');
                    }
                }
            }
        );

        $this
            ->scribe('test:confirm')
            ->expectsConfirmation('Is your name John?', 'no')
            ->expectsOutput('Your name is not John.');

        $this
            ->scribe('test:confirm')
            ->expectsConfirmation('Is your name John?', 'yes')
            ->expectsOutput('Your name is John.');
    }

    public function testAssertionForSelectPromptWithAList(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:select';

                public function handle()
                {
                    $name = select(
                        label: 'What is your name?',
                        options: ['John', 'Jane']
                    );

                    $this->line("Your name is $name.");
                }
            }
        );

        $this
            ->scribe('test:select')
            ->expectsChoice('What is your name?', 'Jane', ['John', 'Jane'])
            ->expectsOutput('Your name is Jane.');
    }

    public function testAssertionForSelectPromptWithAnAssociativeArray(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:select';

                public function handle()
                {
                    $name = select(
                        label: 'What is your name?',
                        options: ['john' => 'John', 'jane' => 'Jane']
                    );

                    $this->line("Your name is $name.");
                }
            }
        );

        $this
            ->scribe('test:select')
            ->expectsChoice('What is your name?', 'jane', ['john' => 'John', 'jane' => 'Jane'])
            ->expectsOutput('Your name is jane.');
    }

    public function testAlternativeAssertionForSelectPromptWithAnAssociativeArray(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:select';

                public function handle()
                {
                    $name = select(
                        label: 'What is your name?',
                        options: ['john' => 'John', 'jane' => 'Jane']
                    );

                    $this->line("Your name is $name.");
                }
            }
        );

        $this
            ->scribe('test:select')
            ->expectsChoice('What is your name?', 'jane', ['john', 'jane', 'John', 'Jane'])
            ->expectsOutput('Your name is jane.');
    }

    public function testAssertionForRequiredMultiselectPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:multiselect';

                public function handle()
                {
                    $names = multiselect(
                        label: 'Which names do you like?',
                        options: ['John', 'Jane', 'Sally', 'Jack'],
                        required: true
                    );

                    $this->line(sprintf('You like %s.', implode(', ', $names)));
                }
            }
        );

        $this
            ->scribe('test:multiselect')
            ->expectsChoice('Which names do you like?', ['John', 'Jane'], ['John', 'Jane', 'Sally', 'Jack'])
            ->expectsOutput('You like John, Jane.');
    }

    public function testAssertionForOptionalMultiselectPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:multiselect';

                public function handle()
                {
                    $names = multiselect(
                        label: 'Which names do you like?',
                        options: ['John', 'Jane', 'Sally', 'Jack'],
                    );

                    if (empty($names)) {
                        $this->line('You like nobody.');
                    } else {
                        $this->line(sprintf('You like %s.', implode(', ', $names)));
                    }
                }
            }
        );

        $this
            ->scribe('test:multiselect')
            ->expectsChoice('Which names do you like?', ['John', 'Jane'], ['John', 'Jane', 'Sally', 'Jack'])
            ->expectsOutput('You like John, Jane.');

        $this
            ->scribe('test:multiselect')
            ->expectsChoice('Which names do you like?', ['None'], ['John', 'Jane', 'Sally', 'Jack'])
            ->expectsOutput('You like nobody.');
    }

    public function testAssertionForSearchPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:search';

                public function handle()
                {
                    $options = collect(['John', 'Jane', 'Sally', 'Jack']);

                    $name = search(
                        label: 'What is your name?',
                        options: fn (string $value) => $value !== ''
                            ? $options->filter(fn ($title) => str_contains($title, $value))->values()->toArray()
                            : []
                    );

                    $this->line("Your name is $name.");
                }
            }
        );

        $this
            ->scribe('test:search')
            ->expectsSearch('What is your name?', 'Jane', 'J', ['John', 'Jane', 'Jack'])
            ->expectsOutput('Your name is Jane.');
    }

    public function testAssertionForMultisearchPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:multisearch';

                public function handle()
                {
                    $options = collect(['John', 'Jane', 'Sally', 'Jack']);

                    $names = multisearch(
                        label: 'Which names do you like?',
                        options: fn (string $value) => $value !== ''
                            ? $options->filter(fn ($title) => str_contains($title, $value))->values()->toArray()
                            : []
                    );

                    if (empty($names)) {
                        $this->line('You like nobody.');
                    } else {
                        $this->line(sprintf('You like %s.', implode(', ', $names)));
                    }
                }
            }
        );

        $this
            ->scribe('test:multisearch')
            ->expectsSearch('Which names do you like?', ['John', 'Jane'], 'J', ['John', 'Jane', 'Jack'])
            ->expectsOutput('You like John, Jane.');

        $this
            ->scribe('test:multisearch')
            ->expectsSearch('Which names do you like?', [], 'J', ['John', 'Jane', 'Jack'])
            ->expectsOutput('You like nobody.');
    }

    public function testAssertionForSelectPromptFollowedByMultisearchPrompt(): void
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:select';

                public function handle()
                {
                    $name = select(
                        label: 'What is your name?',
                        options: ['John', 'Jane']
                    );

                    $titles = collect(['Mr', 'Mrs', 'Ms', 'Dr']);
                    $title = multisearch(
                        label: 'What is your title?',
                        options: fn (string $value) => $value !== ''
                            ? $titles->filter(fn ($title) => str_contains($title, $value))->values()->toArray()
                            : []
                    );

                    $this->line('I will refer to you '.$title[0].' '.$name.'.');
                }
            }
        );

        $this
            ->scribe('test:select')
            ->expectsChoice('What is your name?', 'Jane', ['John', 'Jane'])
            ->expectsSearch('What is your title?', ['Dr'], 'D', ['Dr'])
            ->expectsOutput('I will refer to you Dr Jane.');
    }
}
