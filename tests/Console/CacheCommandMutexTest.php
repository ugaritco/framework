<?php

namespace Heritage\Tests\Console;

use Heritage\Console\CacheCommandMutex;
use Heritage\Console\Command;
use Heritage\Contracts\Cache\Factory;
use Heritage\Contracts\Cache\LockProvider;
use Heritage\Contracts\Cache\Repository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CacheCommandMutexTest extends TestCase
{
    /**
     * @var \Heritage\Console\CacheCommandMutex
     */
    protected $mutex;

    /**
     * @var \Heritage\Console\Command
     */
    protected $command;

    /**
     * @var \Heritage\Contracts\Cache\Factory
     */
    protected $cacheFactory;

    /**
     * @var \Heritage\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    protected function setUp(): void
    {
        $this->cacheFactory = Mockery::mock(Factory::class);
        $this->cacheRepository = Mockery::mock(Repository::class);
        $this->mutex = new CacheCommandMutex($this->cacheFactory);
        $this->command = new class extends Command
        {
            protected $name = 'command-name';
        };
    }

    public function testCanCreateMutex()
    {
        $this->mockUsingCacheStore();
        $this->cacheRepository->expects('add')
            ->andReturn(true);
        $actual = $this->mutex->create($this->command);

        $this->assertTrue($actual);
    }

    public function testCannotCreateMutexIfAlreadyExist()
    {
        $this->mockUsingCacheStore();
        $this->cacheRepository->expects('add')
            ->andReturn(false);
        $actual = $this->mutex->create($this->command);

        $this->assertFalse($actual);
    }

    public function testCanCreateMutexWithCustomConnection()
    {
        $this->mockUsingCacheStore();
        $this->cacheRepository->expects('add')
            ->andReturn(false);
        $this->mutex->useStore('test');

        $this->mutex->create($this->command);
    }

    public function testCanCreateMutexWithLockProvider()
    {
        $lock = $this->mockUsingLockProvider();
        $this->acquireLockExpectations($lock, true);

        $actual = $this->mutex->create($this->command);

        $this->assertTrue($actual);
    }

    public function testCanCreateMutexWithCustomLockProviderConnection()
    {
        $this->mockUsingCacheStore();
        $this->cacheRepository->expects('add')
            ->andReturn(false);
        $this->mutex->useStore('test');

        $this->mutex->create($this->command);
    }

    public function testCannotCreateMutexIfAlreadyExistWithLockProvider()
    {
        $lock = $this->mockUsingLockProvider();
        $this->acquireLockExpectations($lock, false);
        $actual = $this->mutex->create($this->command);

        $this->assertFalse($actual);
    }

    public function testCanCreateMutexWithCustomConnectionWithLockProvider()
    {
        $lock = Mockery::mock(LockProvider::class);
        $this->cacheFactory->expects('store')->once()->with('test')->andReturn($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->twice()->andReturn($lock);

        $this->acquireLockExpectations($lock, true);
        $this->mutex->useStore('test');

        $this->mutex->create($this->command);
    }

    /**
     * @return void
     */
    private function mockUsingCacheStore(): void
    {
        $this->cacheFactory->expects('store')->once()->andReturn($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->andReturn(null);
    }

    private function mockUsingLockProvider(): MockInterface
    {
        $lock = Mockery::mock(LockProvider::class);
        $this->cacheFactory->expects('store')->once()->andReturn($this->cacheRepository);
        $this->cacheRepository->expects('getStore')->twice()->andReturn($lock);

        return $lock;
    }

    private function acquireLockExpectations(MockInterface $lock, bool $acquiresSuccessfully): void
    {
        $lock->expects('lock')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('int'))
            ->andReturns($lock);

        $lock->expects('get')
            ->once()
            ->andReturns($acquiresSuccessfully);
    }

    public function testCommandMutexNameWithoutIsolatedMutexNameMethod()
    {
        $this->mockUsingCacheStore();

        $this->cacheRepository->expects('add')
            ->withArgs(function ($key) {
                $this->assertSame('framework'.DIRECTORY_SEPARATOR.'command-command-name', $key);

                return true;
            })
            ->andReturn(true);

        $this->mutex->create($this->command);
    }

    public function testCommandMutexNameWithIsolatedMutexNameMethod()
    {
        $command = new class extends Command
        {
            protected $name = 'command-name';

            public function isolatableId()
            {
                return 'isolated';
            }
        };

        $this->mockUsingCacheStore();

        $this->cacheRepository->expects('add')
            ->withArgs(function ($key) {
                $this->assertSame('framework'.DIRECTORY_SEPARATOR.'command-command-name-isolated', $key);

                return true;
            })
            ->andReturn(true);

        $this->mutex->create($command);
    }
}
