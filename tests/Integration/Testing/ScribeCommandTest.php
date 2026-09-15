<?php

namespace Heritage\Tests\Integration\Testing;

use Heritage\Console\Command;
use Heritage\Support\Facades\Scribe;
use Mockery;
use Mockery\Exception\InvalidCountException;
use Mockery\Exception\InvalidOrderException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class ScribeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Scribe::command('survey', function () {
            $name = $this->ask('What is your name?');

            $language = $this->choice('Which language do you prefer?', [
                'PHP',
                'Ruby',
                'Python',
            ]);

            $this->line("Your name is $name and you prefer $language.");
        });

        Scribe::command('slim', function () {
            $this->line($this->ask('Who?'));
            $this->line($this->ask('What?'));
            $this->line($this->ask('Huh?'));
        });

        Scribe::command('interactions', function () {
            /** @var Command $this */
            $this->ask('What is your name?');
            $this->choice('Which language do you prefer?', [
                'PHP',
                'PHP',
                'PHP',
            ]);

            $this->table(['Name', 'Email'], [
                ['Taylor Otwell', 'taylor@ugarit.com'],
            ]);

            $this->confirm('Do you want to continue?', true);
        });

        Scribe::command('exit {code}', fn () => (int) $this->argument('code'));

        Scribe::command('contains', function () {
            $this->line('My name is Taylor Otwell');
        });

        Scribe::command('zero', function () {
            $this->line('0');
        });

        Scribe::command('new-england', function () {
            $this->line('The region of New England consists of the following states:');
            $this->info('Connecticut');
            $this->info('Maine');
            $this->info('Massachusetts');
            $this->info('New Hampshire');
            $this->info('Rhode Island');
            $this->info('Vermont');
        });
    }

    public function test_console_command_that_passes()
    {
        $this->scribe('exit', ['code' => 0])->assertOk();
    }

    public function test_console_command_that_fails()
    {
        $this->expectExceptionObject(new AssertionFailedError('Expected status code 0 but received 1.'));

        $this->scribe('exit', ['code' => 1])->assertOk();
    }

    public function test_console_command_that_passes_with_output()
    {
        $this->scribe('survey')
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsQuestion('Which language do you prefer?', 'PHP')
            ->expectsOutput('Your name is Taylor Otwell and you prefer PHP.')
            ->doesntExpectOutput('Your name is Taylor Otwell and you prefer Ruby.')
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_with_repeating_output()
    {
        $this->scribe('slim')
            ->expectsQuestion('Who?', 'Taylor')
            ->expectsQuestion('What?', 'Taylor')
            ->expectsQuestion('Huh?', 'Taylor')
            ->expectsOutput('Taylor')
            ->doesntExpectOutput('Otwell')
            ->expectsOutput('Taylor')
            ->expectsOutput('Taylor')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_unexpected_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "Your name is Taylor Otwell and you prefer PHP." was printed.'));

        $this->scribe('survey')
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsQuestion('Which language do you prefer?', 'PHP')
            ->doesntExpectOutput('Your name is Taylor Otwell and you prefer PHP.')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_unexpected_output_substring()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "Taylor Otwell" was printed.'));

        $this->scribe('contains')
            ->doesntExpectOutputToContain('Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_zero_as_unexpected_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "0" was printed.'));

        $this->scribe('zero')
            ->doesntExpectOutput('0')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_zero_as_unexpected_output_substring()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "0" was printed.'));

        $this->scribe('zero')
            ->doesntExpectOutputToContain('0')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_missing_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "Your name is Taylor Otwell and you prefer PHP." was not printed.'));

        $this->ignoringMockOnceExceptions(function () {
            $this->scribe('survey')
                ->expectsQuestion('What is your name?', 'Taylor Otwell')
                ->expectsQuestion('Which language do you prefer?', 'Ruby')
                ->expectsOutput('Your name is Taylor Otwell and you prefer PHP.')
                ->assertExitCode(0);
        });
    }

    public function test_console_command_that_fails_from_exit_code_mismatch()
    {
        $this->expectExceptionObject(new AssertionFailedError('Expected status code 1 but received 0.'));

        $this->scribe('survey')
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsQuestion('Which language do you prefer?', 'PHP')
            ->assertExitCode(1);
    }

    public function test_console_command_that_fails_from_unordered_output()
    {
        $this->expectException(InvalidOrderException::class);

        $this->ignoringMockOnceExceptions(function () {
            $this->scribe('slim')
                ->expectsQuestion('Who?', 'Taylor')
                ->expectsQuestion('What?', 'Danger')
                ->expectsQuestion('Huh?', 'Otwell')
                ->expectsOutput('Taylor')
                ->expectsOutput('Otwell')
                ->expectsOutput('Danger')
                ->assertExitCode(0);
        });
    }

    public function test_console_command_that_passes_if_the_output_contains()
    {
        $this->scribe('contains')
            ->expectsOutputToContain('Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_outputs_something()
    {
        $this->scribe('contains')
            ->expectsOutput()
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_outputs_is_something_and_is_the_expected_output()
    {
        $this->scribe('contains')
            ->expectsOutput()
            ->expectsOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fail_if_doesnt_output_something()
    {
        $this->expectException(InvalidCountException::class);

        $this->scribe('exit', ['code' => 0])
            ->expectsOutput()
            ->assertExitCode(0);

        $this->verifyMockeryExpectationsNow();
    }

    public function test_console_command_that_fail_if_doesnt_output_something_and_is_not_the_expected_output()
    {
        $this->expectException(AssertionFailedError::class);

        $this->ignoringMockOnceExceptions(function () {
            $this->scribe('exit', ['code' => 0])
                ->expectsOutput()
                ->expectsOutput('My name is Taylor Otwell')
                ->assertExitCode(0);
        });
    }

    public function test_console_command_that_passes_if_does_not_output_anything()
    {
        $this->scribe('exit', ['code' => 0])
            ->doesntExpectOutput()
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_does_not_output_anything_and_is_not_the_expected_output()
    {
        $this->scribe('exit', ['code' => 0])
            ->doesntExpectOutput()
            ->doesntExpectOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_expects_output_and_there_is_interactions()
    {
        $this->scribe('interactions', ['--no-interaction' => true])
            ->expectsOutput()
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'PHP', 'PHP'])
            ->expectsConfirmation('Do you want to continue?', true)
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_but__there_is_interactions()
    {
        $this->expectException(InvalidCountException::class);

        $this->scribe('interactions', ['--no-interaction' => true])
            ->doesntExpectOutput()
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'PHP', 'PHP'])
            ->expectsConfirmation('Do you want to continue?', true)
            ->assertExitCode(0);

        $this->verifyMockeryExpectationsNow();
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_but_outputs_something()
    {
        $this->expectException(InvalidCountException::class);

        $this->scribe('contains')
            ->doesntExpectOutput()
            ->assertExitCode(0);

        $this->verifyMockeryExpectationsNow();
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_and_does_expect_output()
    {
        $this->expectException(InvalidCountException::class);

        $this->scribe('contains')
            ->doesntExpectOutput()
            ->doesntExpectOutput('My name is Taylor Otwell')
            ->assertExitCode(0);

        $this->verifyMockeryExpectationsNow();
    }

    public function test_console_command_that_fails_if_the_output_does_not_contain()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output does not contain "Otwell Taylor".'));

        $this->ignoringMockOnceExceptions(function () {
            $this->scribe('contains')
                ->expectsOutputToContain('Otwell Taylor')
                ->assertExitCode(0);
        });
    }

    public function test_pending_command_can_be_tapped()
    {
        $newEngland = [
            'Connecticut',
            'Maine',
            'Massachusetts',
            'New Hampshire',
            'Rhode Island',
            'Vermont',
        ];

        $this->scribe('new-england')
            ->expectsOutput('The region of New England consists of the following states:')
            ->tap(function ($command) use ($newEngland) {
                foreach ($newEngland as $state) {
                    $command->expectsOutput($state);
                }
            })
            ->assertExitCode(0);
    }

    /**
     * Verify the PendingCommand mock expectations immediately, so an unmet
     * expectation throws here and is caught by the test's expectException().
     */
    protected function verifyMockeryExpectationsNow(): void
    {
        Mockery::close();
    }

    /**
     * Don't allow Mockery's InvalidCountException to be reported. Mocks setup
     * in PendingCommand cause PHPUnit tearDown() to later throw the exception.
     *
     * @param  callable  $callback
     * @return void
     */
    protected function ignoringMockOnceExceptions(callable $callback)
    {
        try {
            $callback();
        } finally {
            try {
                Mockery::close();
            } catch (InvalidCountException) {
                // Ignore mock exception from PendingCommand::expectsOutput().
            }
        }
    }
}
