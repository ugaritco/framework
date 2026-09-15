<?php

namespace Heritage\Tests\Database;

use Heritage\Console\Command;
use Heritage\Console\OutputStyle;
use Heritage\Console\View\Components\Factory;
use Heritage\Container\Container;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Database\ConnectionResolverInterface;
use Heritage\Database\Console\Seeds\SeedCommand;
use Heritage\Database\Console\Seeds\WithoutModelEvents;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Seeder;
use Heritage\Events\NullDispatcher;
use Heritage\Testing\Assert;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class SeedCommandTest extends TestCase
{
    public function testHandle()
    {
        $input = new ArrayInput(['--force' => true, '--database' => 'sqlite']);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = Mockery::mock(Seeder::class);
        $seeder->expects('setContainer')->andReturnSelf();
        $seeder->expects('setCommand')->andReturnSelf();
        $seeder->expects('__invoke');

        $resolver = Mockery::mock(ConnectionResolverInterface::class);
        $resolver->expects('getDefaultConnection');
        $resolver->expects('setDefaultConnection')->with('sqlite');

        $container = Mockery::mock(Container::class);
        $container->expects('call');
        $container->expects('environment')->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->expects('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->expects('make')->with(OutputStyle::class, Mockery::any())->andReturn(
            $outputStyle
        );
        $container->expects('make')->with(Factory::class, Mockery::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setUgarit($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    public function testFailedSeederRestoresPreviousDefaultConnection()
    {
        $input = new ArrayInput(['--force' => true, '--database' => 'sqlite']);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = Mockery::mock(Seeder::class);
        $seeder->expects('setContainer')->andReturnSelf();
        $seeder->expects('setCommand')->andReturnSelf();
        $seeder->expects('__invoke')->andThrow(new RuntimeException('Seeding failed.'));

        $connections = [];

        $resolver = Mockery::mock(ConnectionResolverInterface::class);
        $resolver->expects('getDefaultConnection')->andReturn('mysql');
        $resolver->shouldReceive('setDefaultConnection')->andReturnUsing(function ($name) use (&$connections) {
            $connections[] = $name;
        });

        $container = Mockery::mock(Container::class);
        $container->expects('call');
        $container->expects('environment')->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->expects('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->expects('make')->with(OutputStyle::class, Mockery::any())->andReturn(
            $outputStyle
        );
        $container->expects('make')->with(Factory::class, Mockery::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setUgarit($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);

        try {
            $command->handle();
            $this->fail('Seeding should have failed.');
        } catch (RuntimeException) {
            //
        }

        Assert::assertSame(['sqlite', 'mysql'], $connections);
    }

    public function testWithoutModelEvents()
    {
        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => UserWithoutModelEventsSeeder::class,
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $instance = new UserWithoutModelEventsSeeder();

        $seeder = Mockery::mock($instance);
        $seeder->expects('setContainer')->andReturnSelf();
        $seeder->expects('setCommand')->andReturnSelf();

        $resolver = Mockery::mock(ConnectionResolverInterface::class);
        $resolver->expects('getDefaultConnection');
        $resolver->expects('setDefaultConnection')->with('sqlite');

        $container = Mockery::mock(Container::class);
        $container->expects('call');
        $container->expects('environment')->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->expects('make')->with(UserWithoutModelEventsSeeder::class)->andReturn($seeder);
        $container->expects('make')->with(OutputStyle::class, Mockery::any())->andReturn(
            $outputStyle
        );
        $container->expects('make')->with(Factory::class, Mockery::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setUgarit($container);

        $dispatcher = Mockery::mock(Dispatcher::class);
        Model::setEventDispatcher($dispatcher);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        Assert::assertSame($dispatcher, Model::getEventDispatcher());

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    public function testProhibitable()
    {
        $input = new ArrayInput([]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $resolver = Mockery::mock(ConnectionResolverInterface::class);

        $container = Mockery::mock(Container::class);
        $container->expects('call');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->expects('make')->with(OutputStyle::class, Mockery::any())->andReturn(
            $outputStyle
        );
        $container->expects('make')->with(Factory::class, Mockery::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setUgarit($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);

        SeedCommand::prohibit();

        Assert::assertSame(Command::FAILURE, $command->handle());
    }

    protected function tearDown(): void
    {
        SeedCommand::prohibit(false);

        Model::unsetEventDispatcher();
    }
}

class UserWithoutModelEventsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run()
    {
        Assert::assertInstanceOf(NullDispatcher::class, Model::getEventDispatcher());
    }
}
