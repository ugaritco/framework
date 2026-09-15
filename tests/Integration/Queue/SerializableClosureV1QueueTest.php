<?php

namespace Heritage\Tests\Integration\Queue;

use Heritage\Foundation\Testing\RefreshDatabase;
use Heritage\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

#[WithMigration]
#[WithEnv('APP_KEY', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF')]
class SerializableClosureV1QueueTest extends TestCase
{
    use RefreshDatabase;

    /** {@inheritDoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $this->markTestSkippedWhen($this->usingInMemoryDatabase(), 'Test does not support using :memory: database connection');

        tap($app->make('config'), function ($config) {
            $config->set([
                'queue.default' => 'database',
            ]);
        });
    }

    /** {@inheritDoc} */
    protected function afterRefreshingDatabase()
    {
        UserFactory::new()->create([
            'id' => 100,
            'name' => 'Taylor Otwell',
            'email' => 'taylor@ugarit.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ]);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => "{\"uuid\":\"d7c0856d-733a-4e73-89c8-eca4dea621ff\",\"displayName\":\"Heritage\\\\Tests\\\\Integration\\\\Queue\\\\Fixtures\\\\Jobs\\\\DeleteUser\",\"job\":\"Heritage\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Heritage\\\\Tests\\\\Integration\\\\Queue\\\\Fixtures\\\\Jobs\\\\DeleteUser\",\"command\":\"O:59:\\\"Heritage\\\\Tests\\\\Integration\\\\Queue\\\\Fixtures\\\\Jobs\\\\DeleteUser\\\":3:{s:4:\\\"user\\\";O:45:\\\"Heritage\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:31:\\\"Heritage\\\\Foundation\\\\Auth\\\\User\\\";s:2:\\\"id\\\";i:100;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:6:\\\"sqlite\\\";s:15:\\\"collectionClass\\\";N;}s:7:\\\"chained\\\";a:1:{i:0;s:571:\\\"O:34:\\\"Heritage\\\\Queue\\\\CallQueuedClosure\\\":1:{s:7:\\\"closure\\\";O:47:\\\"Ugarit\\\\SerializableClosure\\\\SerializableClosure\\\":1:{s:12:\\\"serializable\\\";O:46:\\\"Ugarit\\\\SerializableClosure\\\\Serializers\\\\Signed\\\":2:{s:12:\\\"serializable\\\";s:282:\\\"O:46:\\\"Ugarit\\\\SerializableClosure\\\\Serializers\\\\Native\\\":5:{s:3:\\\"use\\\";a:0:{}s:8:\\\"function\\\";s:57:\\\"function () {\\n            \\\\info('Hello world');\\n        }\\\";s:5:\\\"scope\\\";s:44:\\\"Heritage\\\\Foundation\\\\Console\\\\ClosureCommand\\\";s:4:\\\"this\\\";N;s:4:\\\"self\\\";s:32:\\\"000000000000021e0000000000000000\\\";}\\\";s:4:\\\"hash\\\";s:44:\\\"VGMlRmFr2\\/U1E8lksExnzODwffyWR8oD01WOcQ2SUjE=\\\";}}}\\\";}s:19:\\\"chainCatchCallbacks\\\";a:1:{i:0;O:47:\\\"Ugarit\\\\SerializableClosure\\\\SerializableClosure\\\":1:{s:12:\\\"serializable\\\";O:46:\\\"Ugarit\\\\SerializableClosure\\\\Serializers\\\\Signed\\\":2:{s:12:\\\"serializable\\\";s:309:\\\"O:46:\\\"Ugarit\\\\SerializableClosure\\\\Serializers\\\\Native\\\":5:{s:3:\\\"use\\\";a:0:{}s:8:\\\"function\\\";s:84:\\\"function (\\\\Throwable \$e) {\\n        \\\\Heritage\\\\Support\\\\Facades\\\\Log::error(\$e);\\n    }\\\";s:5:\\\"scope\\\";s:44:\\\"Heritage\\\\Foundation\\\\Console\\\\ClosureCommand\\\";s:4:\\\"this\\\";N;s:4:\\\"self\\\";s:32:\\\"00000000000002380000000000000000\\\";}\\\";s:4:\\\"hash\\\";s:44:\\\"RBSD4RFLgmKL9WJEGY66aeZtWDkX\\/aY1J+MJ8LQSYi4=\\\";}}}}\"}}",
            'attempts' => 0,
            'available_at' => 1731919764,
            'created_at' => 1731919764,
        ]);
    }

    public function testItCanProcessQueueFromSerializableClosureV1()
    {
        $this->assertDatabaseHas('users', [
            'name' => 'Taylor Otwell',
            'email' => 'taylor@ugarit.com',
        ]);

        $this->scribe('queue:work', [
            'connection' => 'database',
            '--stop-when-empty' => true,
            '--memory' => 1024,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('users', [
            'name' => 'Taylor Otwell',
            'email' => 'taylor@ugarit.com',
        ]);
    }
}
