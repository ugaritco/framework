<?php

namespace Heritage\Tests\Foundation\Cloud;

use Heritage\Foundation\Cloud\CloudManager;
use Heritage\Foundation\Cloud\Queue as CloudQueue;
use Heritage\Support\Facades\Cloud;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

class CloudManagerTest extends TestCase
{
    #[TestWith([null, false])]
    #[TestWith(['sqs', false])]
    #[TestWith(['cloud', true])]
    public function testUsesManagedQueuesReflectsTheCloudConnectionDriver(?string $driver, bool $managed)
    {
        config(['queue.connections.cloud.driver' => $driver]);

        $this->assertSame($managed, Cloud::usesManagedQueues());
    }

    public function testQueueThrowsWhenManagedQueuesAreNotConfigured()
    {
        $this->expectExceptionObject(new RuntimeException(
            'Ugarit Cloud managed queues are not configured for this application.'
        ));

        Cloud::queue();
    }

    #[TestWith(['emails', true])]
    #[TestWith(['exports', false])]
    public function testIsManagedQueueChecksTheConfiguredManagedQueues(string $queue, bool $managed)
    {
        $cloudQueue = Mockery::mock(CloudQueue::class);
        $cloudQueue->shouldReceive('managedQueues')->andReturn(['emails']);

        $cloud = Cloud::partialMock();
        $cloud->shouldReceive('usesManagedQueues')->andReturn(true);
        $cloud->shouldReceive('queue')->andReturn($cloudQueue);

        $this->assertSame($managed, Cloud::isManagedQueue($queue));
    }

    public function testFacadeResolvesTheCloudManager()
    {
        $this->assertInstanceOf(CloudManager::class, Cloud::getFacadeRoot());
    }

    public function testCloudManagerIsMacroable()
    {
        CloudManager::macro('foo', fn () => 'bar');

        $this->assertSame('bar', Cloud::foo());
    }
}
