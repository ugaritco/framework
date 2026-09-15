<?php

namespace Heritage\Tests\Database;

use Heritage\Database\Eloquent\Collection;
use Heritage\Http\Resources\Json\AnonymousResourceCollection;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Tests\Database\Fixtures\Models\EloquentResourceCollectionTestModel;
use Heritage\Tests\Database\Fixtures\Models\EloquentResourceTestResourceModelWithUseResourceAttribute;
use Heritage\Tests\Database\Fixtures\Models\EloquentResourceTestResourceModelWithUseResourceCollectionAttribute;
use Heritage\Tests\Database\Fixtures\Resources\EloquentResourceCollectionTestResource;
use Heritage\Tests\Database\Fixtures\Resources\EloquentResourceTestJsonResource;
use Heritage\Tests\Database\Fixtures\Resources\EloquentResourceTestJsonResourceCollection;
use LogicException;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentResourceCollectionTest extends TestCase
{
    public function testItCanTransformToExplicitResource()
    {
        $collection = new Collection([
            new EloquentResourceCollectionTestModel(),
        ]);

        $resource = $collection->toResourceCollection(EloquentResourceCollectionTestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectExceptionObject(new LogicException('Failed to find resource class for model [Heritage\Tests\Database\Fixtures\Models\EloquentResourceCollectionTestModel].'));

        $collection = new Collection([
            new EloquentResourceCollectionTestModel(),
        ]);
        $collection->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $collection = new Collection([
            new EloquentResourceCollectionTestModel(),
        ]);

        class_alias(EloquentResourceCollectionTestResource::class, 'Heritage\Tests\Database\Fixtures\Http\Resources\EloquentResourceCollectionTestModelResource');

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItCanTransformToResourceViaUseResourceAttribute()
    {
        $collection = new Collection([
            new EloquentResourceTestResourceModelWithUseResourceCollectionAttribute(),
        ]);

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(EloquentResourceTestJsonResourceCollection::class, $resource);
    }

    public function testItCanTransformToResourceViaUseResourceCollectionAttribute()
    {
        $collection = new Collection([
            new EloquentResourceTestResourceModelWithUseResourceAttribute(),
        ]);

        $resource = $collection->toResourceCollection();

        $this->assertInstanceOf(AnonymousResourceCollection::class, $resource);
        $this->assertInstanceOf(EloquentResourceTestJsonResource::class, $resource[0]);
    }
}
