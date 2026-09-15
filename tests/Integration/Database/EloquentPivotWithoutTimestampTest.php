<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Tests\Integration\Database\Fixtures\EloquentPivotWithoutTimestamp as App;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;

require_once __DIR__.'/Fixtures/EloquentPivotWithoutTimestamp/Models.php';

#[WithConfig('auth.providers.users.model', App\User::class)]
#[WithMigration]
class EloquentPivotWithoutTimestampTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        App\migrate();
    }

    public function testAttachingModelWithoutTimestamps()
    {
        $now = $this->freezeSecond();

        $user = App\User::factory()->create();
        $role = App\Role::factory()->create();

        $user->roles()->attach($role->getKey(), ['notes' => 'Ugarit']);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->getKey(),
            'role_id' => $role->getKey(),
            'notes' => 'Ugarit',
            'created_at' => $now,
        ]);
    }
}
