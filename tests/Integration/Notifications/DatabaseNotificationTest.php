<?php

namespace Heritage\Tests\Integration\Notifications;

use Heritage\Database\Eloquent\Casts\AsStringable;
use Heritage\Database\Eloquent\Concerns\HasUuids;
use Heritage\Database\Schema\Blueprint;
use Heritage\Foundation\Testing\RefreshDatabase;
use Heritage\Notifications\Notifiable;
use Heritage\Support\Facades\Notification;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Notifications\Fixtures\NotificationStub;
use Orchestra\Testbench\Attributes\DefineDatabase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration('ugarit', 'notifications')]
class DatabaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[DefineDatabase('defineDatabaseAndConvertUserIdToUuid')]
    public function testAssertSentToWhenNotifiableHasStringableKey()
    {
        Notification::fake();

        $user = UuidUserFactoryStub::new()->create();

        $user->notify(new NotificationStub);

        Notification::assertSentTo($user, NotificationStub::class, function ($notification, $channels, $notifiable) use ($user) {
            return $notifiable === $user;
        });
    }

    /**
     * Define database and convert User's ID to UUID.
     *
     * @param  \Heritage\Foundation\Application  $app
     * @return void
     */
    protected function defineDatabaseAndConvertUserIdToUuid($app): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->change();
        });
    }
}

class UuidUserFactoryStub extends \Orchestra\Testbench\Factories\UserFactory
{
    protected $model = UuidUserStub::class;
}

class UuidUserStub extends \Heritage\Foundation\Auth\User
{
    use HasUuids, Notifiable;

    protected $table = 'users';

    #[\Override]
    public function casts()
    {
        return array_merge(parent::casts(), ['id' => AsStringable::class]);
    }
}
