<?php

namespace Heritage\Tests\Foundation\Console;

use Heritage\Console\Application;
use Heritage\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Heritage\Foundation\Application as FoundationApplication;
use Heritage\Foundation\Console\ChannelListCommand;
use Heritage\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class ChannelListCommandTest extends TestCase
{
    public function testItDisplaysAnErrorWhenThereAreNoChannels(): void
    {
        $app = $this->makeApplication([]);

        $app->call('channel:list');

        $this->assertStringContainsString(
            "doesn't have any private broadcasting channels", $app->output()
        );
    }

    public function testItListsRegisteredChannels(): void
    {
        $app = $this->makeApplication([
            'orders.{order}' => fn () => true,
        ]);

        $app->call('channel:list');

        $output = $app->output();

        $this->assertStringContainsString('orders.{order}', $output);
        $this->assertStringContainsString('Showing [1] private channels', $output);
    }

    protected function makeApplication(array $channels): Application
    {
        $ugarit = new FoundationApplication(__DIR__);

        $broadcaster = Mockery::mock(BroadcasterContract::class);
        $broadcaster->expects('getChannels')->andReturn(new Collection($channels));

        $ugarit->instance(BroadcasterContract::class, $broadcaster);

        $scribe = new Application(
            $ugarit,
            new \Heritage\Events\Dispatcher($ugarit),
            'testing'
        );

        $command = new ChannelListCommand;
        $command->setUgarit($ugarit);

        $scribe->addCommands([$command]);

        return $scribe;
    }
}
