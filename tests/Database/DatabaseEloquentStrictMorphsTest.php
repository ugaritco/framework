<?php

namespace Heritage\Tests\Database;

use Heritage\Database\ClassMorphViolationException;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\Relations\Pivot;
use Heritage\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentStrictMorphsTest extends TestCase
{
    protected function setUp(): void
    {
        Relation::requireMorphMap();
    }

    public function testStrictModeThrowsAnExceptionOnClassMap()
    {
        $this->expectException(ClassMorphViolationException::class);

        $model = new TestModel;

        $model->getMorphClass();
    }

    public function testStrictModeDoesNotThrowExceptionWhenMorphMap()
    {
        $model = new TestModel;

        Relation::morphMap([
            'test' => TestModel::class,
        ]);

        $morphName = $model->getMorphClass();
        $this->assertSame('test', $morphName);
    }

    public function testMapsCanBeEnforcedInOneMethod()
    {
        $model = new TestModel;

        Relation::requireMorphMap(false);

        Relation::enforceMorphMap([
            'test' => TestModel::class,
        ]);

        $morphName = $model->getMorphClass();
        $this->assertSame('test', $morphName);
    }

    public function testMapIgnoreGenericPivotClass()
    {
        $pivotModel = new Pivot();

        $pivotModel->getMorphClass();
    }

    public function testMapCanBeEnforcedToCustomPivotClass()
    {
        $this->expectException(ClassMorphViolationException::class);

        $pivotModel = new TestPivotModel();

        $pivotModel->getMorphClass();
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);
        Relation::requireMorphMap(false);
    }
}

class TestModel extends Model
{
}

class TestPivotModel extends Pivot
{
}
