<?php

namespace Heritage\Tests\Integration\Concurrency\Console;

use Heritage\Concurrency\Console\InvokeSerializedClosureCommand;
use Heritage\Contracts\Console\Kernel;
use Heritage\Support\Facades\Scribe;
use Ugarit\SerializableClosure\SerializableClosure;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

class CustomParameterException extends RuntimeException
{
    public function __construct(
        public string $customParam,
        string $message = '',
    ) {
        parent::__construct($message ?: "Exception with param: {$customParam}");
    }
}

class InvokeSerializedClosureCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new InvokeSerializedClosureCommand);
    }

    public function testItCanInvokeSerializedClosureFromArgument()
    {
        // Create a simple closure and serialize it
        $closure = fn () => 'Hello, World!';
        $serialized = serialize(new SerializableClosure($closure));

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command with the serialized closure
        Scribe::call('invoke-serialized-closure', [
            'code' => $serialized,
        ], $output);

        // Get the output and decode it
        $result = json_decode($output->fetch(), true);

        // Verify the result
        $this->assertTrue($result['successful']);
        $this->assertSame('Hello, World!', unserialize($result['result']));
    }

    public function testItCanInvokeSerializedClosureFromEnvironment()
    {
        // Create a simple closure and serialize it
        $closure = fn () => 'From Environment';
        $serialized = serialize(new SerializableClosure($closure));

        // Set the environment variable
        $_SERVER['UGARIT_INVOKABLE_CLOSURE'] = base64_encode($serialized);

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command without arguments
        Scribe::call('invoke-serialized-closure', [], $output);

        // Get the output and decode it
        $result = json_decode($output->fetch(), true);

        // Verify the result
        $this->assertTrue($result['successful']);
        $this->assertSame('From Environment', unserialize($result['result']));

        // Clean up
        unset($_SERVER['UGARIT_INVOKABLE_CLOSURE']);
    }

    public function testItReturnsNullWhenNoClosureIsProvided()
    {
        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command without arguments
        Scribe::call('invoke-serialized-closure', [], $output);

        // Get the output and decode it
        $result = json_decode($output->fetch(), true);

        // Verify the result
        $this->assertTrue($result['successful']);
        $this->assertNull(unserialize($result['result']));
    }

    public function testItHandlesExceptionsGracefully()
    {
        // Create a closure that throws an exception
        $closure = fn () => throw new RuntimeException('Test exception');
        $serialized = serialize(new SerializableClosure($closure));

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command with the serialized closure
        Scribe::call('invoke-serialized-closure', [
            'code' => $serialized,
        ], $output);

        // Get the output and decode it
        $result = json_decode($output->fetch(), true);

        // Verify the exception was caught
        $this->assertFalse($result['successful']);
        $this->assertSame('RuntimeException', $result['exception']);
        $this->assertSame('Test exception', $result['message']);
    }

    public function testItHandlesCustomExceptionWithParameters()
    {
        // Create a closure that throws an exception with parameters
        $closure = fn () => throw new CustomParameterException('Test param');
        $serialized = serialize(new SerializableClosure($closure));

        // Create a new output buffer
        $output = new BufferedOutput;

        // Call the command with the serialized closure
        Scribe::call('invoke-serialized-closure', [
            'code' => $serialized,
        ], $output);

        // Get the output and decode it
        $result = json_decode($output->fetch(), true);

        // Verify the exception was caught and parameters were captured
        $this->assertFalse($result['successful']);
        $this->assertArrayHasKey('parameters', $result);
        $this->assertSame('Test param', $result['parameters']['customParam'] ?? null);
    }
}
