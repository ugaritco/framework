<?php

namespace Heritage\Tests\Integration\Database\MySql;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\DB;
use Heritage\Support\Facades\Schema;

class DatabaseEloquentMySqlIntegrationTest extends MySqlTestCase
{
    protected function afterRefreshingDatabase()
    {
        if (! Schema::hasTable('database_eloquent_mysql_integration_users')) {
            Schema::create('database_eloquent_mysql_integration_users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamps();
            });
        }
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('database_eloquent_mysql_integration_users');
    }

    public function testCreateOrFirst()
    {
        $user1 = DatabaseEloquentMySqlIntegrationUser::createOrFirst(['email' => 'taylorotwell@gmail.com']);

        $this->assertSame('taylorotwell@gmail.com', $user1->email);
        $this->assertNull($user1->name);

        $user2 = DatabaseEloquentMySqlIntegrationUser::createOrFirst(
            ['email' => 'taylorotwell@gmail.com'],
            ['name' => 'Taylor Otwell']
        );

        $this->assertEquals($user1->id, $user2->id);
        $this->assertSame('taylorotwell@gmail.com', $user2->email);
        $this->assertNull($user2->name);

        $user3 = DatabaseEloquentMySqlIntegrationUser::createOrFirst(
            ['email' => 'abigailotwell@gmail.com'],
            ['name' => 'Abigail Otwell']
        );

        $this->assertNotEquals($user3->id, $user1->id);
        $this->assertSame('abigailotwell@gmail.com', $user3->email);
        $this->assertSame('Abigail Otwell', $user3->name);

        $user4 = DatabaseEloquentMySqlIntegrationUser::createOrFirst(
            ['name' => 'Dries Vints'],
            ['name' => 'Nuno Maduro', 'email' => 'nuno@ugarit.com']
        );

        $this->assertSame('Nuno Maduro', $user4->name);
    }

    public function testCreateOrFirstWithinTransaction()
    {
        $user1 = DatabaseEloquentMySqlIntegrationUser::createOrFirst(['email' => 'taylor@ugarit.com']);

        DB::transaction(function () use ($user1) {
            $user2 = DatabaseEloquentMySqlIntegrationUser::createOrFirst(
                ['email' => 'taylor@ugarit.com'],
                ['name' => 'Taylor Otwell']
            );

            $this->assertEquals($user1->id, $user2->id);
            $this->assertSame('taylor@ugarit.com', $user2->email);
            $this->assertNull($user2->name);
        });
    }
}

class DatabaseEloquentMySqlIntegrationUser extends Model
{
    protected $table = 'database_eloquent_mysql_integration_users';

    protected $guarded = [];
}
