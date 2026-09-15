<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Database\Eloquent\Collection as EloquentCollection;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Database\Fixtures\Models\Integration\User;

class EloquentCollectionFreshTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
        });
    }

    public function testEloquentCollectionFresh()
    {
        User::insert([
            ['email' => 'ugarit@framework.com'],
            ['email' => 'ugarit@ugarit.com'],
        ]);

        $collection = User::all();

        $collection->first()->delete();

        $freshCollection = $collection->fresh();

        $this->assertCount(1, $freshCollection);
        $this->assertInstanceOf(EloquentCollection::class, $freshCollection);
    }
}
