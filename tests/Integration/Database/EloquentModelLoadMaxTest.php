<?php

namespace Heritage\Tests\Integration\Database\EloquentModelLoadMaxTest;

use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\DB;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Database\Fixtures\Models\LoadAggregate\BaseModel;
use Heritage\Tests\Database\Fixtures\Models\LoadAggregate\Related1;
use Heritage\Tests\Database\Fixtures\Models\LoadAggregate\Related2;
use Heritage\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelLoadMaxTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('base_models', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('related1s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
            $table->integer('number');
        });

        Schema::create('related2s', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_model_id');
            $table->integer('number');
        });

        BaseModel::create();

        Related1::create(['base_model_id' => 1, 'number' => 10]);
        Related1::create(['base_model_id' => 1, 'number' => 11]);
        Related2::create(['base_model_id' => 1, 'number' => 12]);
        Related2::create(['base_model_id' => 1, 'number' => 13]);
    }

    public function testLoadMaxSingleRelation()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadMax('related1', 'number');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(11, $model->related1_max_number);
    }

    public function testLoadMaxMultipleRelations()
    {
        $model = BaseModel::first();

        DB::enableQueryLog();

        $model->loadMax(['related1', 'related2'], 'number');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals(11, $model->related1_max_number);
        $this->assertEquals(13, $model->related2_max_number);
    }
}
