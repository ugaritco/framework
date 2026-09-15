<?php

namespace Heritage\Tests\Http\Resources\JsonApi;

use Heritage\Database\Eloquent\Collection as EloquentCollection;
use Heritage\Database\Eloquent\Model;
use Heritage\Http\Resources\JsonApi\JsonApiResource;
use Heritage\Http\Resources\JsonApi\RelationResolver;
use PHPUnit\Framework\TestCase;

class RelationResolverTest extends TestCase
{
    public function testResolvesClosureReturningResourceCollection()
    {
        $first = new RelationResolverTestModel(['id' => 1]);
        $second = new RelationResolverTestModel(['id' => 2]);

        $resolver = new RelationResolver('comments', fn () => RelationResolverTestResource::collection([$first, $second]));

        $resolved = $resolver->handle(new RelationResolverTestModel);

        $this->assertInstanceOf(EloquentCollection::class, $resolved);
        $this->assertSame([$first, $second], $resolved->all());
        $this->assertSame(RelationResolverTestResource::class, $resolver->resourceClass());
    }

    public function testResolvesClosureReturningSingleResource()
    {
        $model = new RelationResolverTestModel(['id' => 1]);

        $resolver = new RelationResolver('author', fn () => new RelationResolverTestResource($model));

        $resolved = $resolver->handle(new RelationResolverTestModel);

        $this->assertSame($model, $resolved);
        $this->assertSame(RelationResolverTestResource::class, $resolver->resourceClass());
    }

    public function testResolvesClosureReturningRawModels()
    {
        $model = new RelationResolverTestModel(['id' => 1]);

        $resolver = new RelationResolver('comments', fn () => new EloquentCollection([$model]));

        $resolved = $resolver->handle(new RelationResolverTestModel);

        $this->assertInstanceOf(EloquentCollection::class, $resolved);
        $this->assertSame([$model], $resolved->all());
        $this->assertNull($resolver->resourceClass());
    }

    public function testResolvesClosureReturningNull()
    {
        $resolver = new RelationResolver('author', fn () => null);

        $this->assertNull($resolver->handle(new RelationResolverTestModel));
        $this->assertNull($resolver->resourceClass());
    }
}

class RelationResolverTestModel extends Model
{
    protected $guarded = [];
}

class RelationResolverTestResource extends JsonApiResource
{
    //
}
