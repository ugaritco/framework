<?php

namespace Heritage\Tests\Integration\Database;

use Heritage\Contracts\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\Attributes\Scope;
use Heritage\Database\Eloquent\Model;

class EloquentModelScopeTest extends DatabaseTestCase
{
    public function testModelHasScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasNamedScope('exists'));
    }

    public function testModelDoesNotHaveScope()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasNamedScope('doesNotExist'));
    }

    public function testModelHasAttributedScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasNamedScope('existsAsWell'));
    }

    public function testModelDoesNotHaveScopeWhenPrivateVisibility()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasNamedScope('existsAsPrivate'));
    }
}

class TestScopeModel1 extends Model
{
    public function scopeExists(Builder $builder)
    {
        return $builder;
    }

    #[Scope]
    protected function existsAsWell(Builder $builder)
    {
        return $builder;
    }

    #[Scope]
    private function existsAsPrivate(Builder $builder)
    {
        return $builder;
    }
}
