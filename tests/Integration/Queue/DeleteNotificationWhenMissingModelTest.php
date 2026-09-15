<?php

namespace Heritage\Tests\Integration\Queue;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Schema\Blueprint;
use Heritage\Notifications\Notifiable;
use Heritage\Support\Facades\Notification as NotificationFacade;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Notifications\Fixtures\DeleteWhenMissingNotification;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class DeleteNotificationWhenMissingModelTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('queue.default', 'database');
        $this->driver = 'database';
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('delete_notification_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('delete_notification_test_models');
    }

    protected function tearDown(): void
    {
        DeleteWhenMissingNotification::$sent = false;

        parent::tearDown();
    }

    public function test_deleteModelWhenMissing_on_queued_notification(): void
    {
        $model = DeleteNotificationTestModel::query()->create(['name' => 'test']);

        NotificationFacade::send($model, new DeleteWhenMissingNotification($model));

        DeleteNotificationTestModel::query()->where('name', 'test')->delete();

        $this->runQueueWorkerCommand(['--once' => '1']);

        $this->assertFalse(DeleteWhenMissingNotification::$sent);
        $this->assertNull(\DB::table('failed_jobs')->first());
    }
}

class DeleteNotificationTestModel extends Model
{
    use Notifiable;

    protected $table = 'delete_notification_test_models';

    public $timestamps = false;

    protected $guarded = [];
}
