<?php

namespace Heritage\Tests\Integration\Queue;

use Heritage\Bus\Queueable;
use Heritage\Contracts\Encryption\DecryptException;
use Heritage\Contracts\Queue\ShouldBeEncrypted;
use Heritage\Contracts\Queue\ShouldQueue;
use Heritage\Foundation\Bus\Dispatchable;
use Heritage\Foundation\Testing\DatabaseMigrations;
use Heritage\Support\Facades\Bus;
use Heritage\Support\Facades\DB;
use Heritage\Support\Facades\Queue;
use Heritage\Support\Str;
use Heritage\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class JobEncryptionTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('queue.default', 'database');
    }

    protected function tearDown(): void
    {
        JobEncryptionTestEncryptedJob::$ran = false;
        JobEncryptionTestNonEncryptedJob::$ran = false;

        parent::tearDown();
    }

    public function testEncryptedJobPayloadIsStoredEncrypted()
    {
        Bus::dispatch(new JobEncryptionTestEncryptedJob);

        $this->assertNotEmpty(
            decrypt(json_decode(DB::table('jobs')->first()->payload)->data->command)
        );
    }

    public function testNonEncryptedJobPayloadIsStoredRaw()
    {
        Bus::dispatch(new JobEncryptionTestNonEncryptedJob);

        $this->expectExceptionObject(new DecryptException('The payload is invalid'));

        $this->assertInstanceOf(JobEncryptionTestNonEncryptedJob::class,
            unserialize(json_decode(DB::table('jobs')->first()->payload)->data->command)
        );

        decrypt(json_decode(DB::table('jobs')->first()->payload)->data->command);
    }

    public function testQueueCanProcessEncryptedJob()
    {
        Bus::dispatch(new JobEncryptionTestEncryptedJob);

        Queue::pop()->fire();

        $this->assertTrue(JobEncryptionTestEncryptedJob::$ran);
    }

    public function testQueueCanProcessUnEncryptedJob()
    {
        Bus::dispatch(new JobEncryptionTestNonEncryptedJob);

        Queue::pop()->fire();

        $this->assertTrue(JobEncryptionTestNonEncryptedJob::$ran);
    }
}

class JobEncryptionTestEncryptedJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class JobEncryptionTestNonEncryptedJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}
