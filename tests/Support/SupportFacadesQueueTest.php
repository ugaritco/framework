<?php

namespace Heritage\Tests\Support;

use Heritage\Bus\Queueable;
use Heritage\Container\Container;
use Heritage\Contracts\Queue\Factory as QueueContract;
use Heritage\Support\Facades\Facade;
use Heritage\Support\Facades\Queue;
use Heritage\Support\Testing\Fakes\QueueFake;
use Mockery;
use PHPUnit\Framework\TestCase;

class SupportFacadesQueueTest extends TestCase
{
    private $queueManager;

    protected function setUp(): void
    {
        $this->queueManager = Mockery::mock(Factory::class);

        $container = new Container;
        $container->instance('queue', $this->queueManager);
        $container->alias('queue', QueueContract::class);

        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Queue::clearResolvedInstance();
        Queue::setFacadeApplication(null);
    }

    public function testFakeFor()
    {
        Queue::fakeFor(function () {
            (new QueueForStub)->pushJob();

            Queue::assertPushed(QueueJobStub::class);
        });

        $this->queueManager->expects('push');

        (new QueueForStub)->pushJob();
    }

    public function testFakeForSwapsQueueManager()
    {
        Queue::fakeFor(function () {
            $this->assertInstanceOf(QueueFake::class, Queue::getFacadeRoot());
        });

        $this->assertSame($this->queueManager, Queue::getFacadeRoot());
    }

    public function testFakeExcept()
    {
        $fake = Queue::fakeExcept(QueueJobStub::class);

        $this->assertInstanceOf(QueueFake::class, $fake);
        $this->assertSame($fake, Queue::getFacadeRoot());
    }

    public function testFakeExceptFor()
    {
        Queue::fakeExceptFor(function () {
            $this->assertInstanceOf(QueueFake::class, Queue::getFacadeRoot());
        }, [QueueJobStub::class]);

        $this->assertSame($this->queueManager, Queue::getFacadeRoot());
    }

    public function testFakeExceptForSwapsQueueManager()
    {
        Queue::fakeExceptFor(function () {
            $this->assertInstanceOf(QueueFake::class, Queue::getFacadeRoot());
        }, []);

        $this->assertSame($this->queueManager, Queue::getFacadeRoot());
    }

    public function testFakeExceptForReturnValue()
    {
        $result = Queue::fakeExceptFor(function () {
            return 'test-result';
        });

        $this->assertSame('test-result', $result);
    }

    public function testFakeForReturnValue()
    {
        $result = Queue::fakeFor(function () {
            return 'test-result';
        });

        $this->assertSame('test-result', $result);
    }
}

class QueueJobStub
{
    use Queueable;
}

class OtherQueueJobStub
{
    use Queueable;
}

class QueueForStub
{
    public function pushJob()
    {
        Queue::push(new QueueJobStub);
    }
}
