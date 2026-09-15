<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Contracts\Events\Dispatcher;
use Heritage\Database\Eloquent\MassPrunable;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\SoftDeletes;
use Heritage\Database\Events\ModelsPruned;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Carbon;
use Heritage\Support\Facades\Schema;
use LogicException;
use Mockery;

class EloquentMassPrunableTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(Dispatcher::class, function () {
            return Mockery::mock(Dispatcher::class);
        });

        $this->app->alias(Dispatcher::class, 'events');
    }

    protected function afterRefreshingDatabase()
    {
        collect([
            'mass_prunable_test_models',
            'mass_prunable_soft_delete_test_models',
            'mass_prunable_test_model_missing_prunable_methods',
        ])->each(function ($table) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->softDeletes();
                $table->boolean('pruned')->default(false);
                $table->timestamps();
            });
        });
    }

    public function testPrunableMethodMustBeImplemented()
    {
        $this->expectExceptionObject(new LogicException('Please implement'));

        MassPrunableTestModelMissingPrunableMethod::create()->pruneAll();
    }

    public function testPrunesRecords()
    {
        app('events')
            ->expects('dispatch')
            ->times(2)
            ->with(Mockery::type(ModelsPruned::class));

        collect(range(1, 5000))->map(function ($id) {
            return ['name' => 'foo'];
        })->chunk(200)->each(function ($chunk) {
            MassPrunableTestModel::insert($chunk->all());
        });

        $count = (new MassPrunableTestModel)->pruneAll();

        $this->assertEquals(1500, $count);
        $this->assertEquals(3500, MassPrunableTestModel::count());
    }

    public function testPrunesSoftDeletedRecords()
    {
        app('events')
            ->expects('dispatch')
            ->times(3)
            ->with(Mockery::type(ModelsPruned::class));

        collect(range(1, 5000))->map(function ($id) {
            return ['deleted_at' => Carbon::now()];
        })->chunk(200)->each(function ($chunk) {
            MassPrunableSoftDeleteTestModel::insert($chunk->all());
        });

        $count = (new MassPrunableSoftDeleteTestModel)->pruneAll();

        $this->assertEquals(3000, $count);
        $this->assertEquals(0, MassPrunableSoftDeleteTestModel::count());
        $this->assertEquals(2000, MassPrunableSoftDeleteTestModel::withTrashed()->count());
    }
}

class MassPrunableTestModel extends Model
{
    use MassPrunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1500);
    }
}

class MassPrunableSoftDeleteTestModel extends Model
{
    use MassPrunable, SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 3000);
    }
}

class MassPrunableTestModelMissingPrunableMethod extends Model
{
    use MassPrunable;
}
