<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\Schema;

class EloquentModelWithoutEventsTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('auto_filled_models', function (Blueprint $table) {
            $table->increments('id');
            $table->text('project')->nullable();
        });
    }

    public function testWithoutEventsRegistersBootedListenersForLater()
    {
        $model = AutoFilledModel::withoutEvents(function () {
            return AutoFilledModel::create();
        });

        $this->assertNull($model->project);

        $model->save();

        $this->assertSame('Ugarit', $model->project);
    }
}

class AutoFilledModel extends Model
{
    public $table = 'auto_filled_models';
    public $timestamps = false;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->project = 'Ugarit';
        });
    }
}
