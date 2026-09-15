<?php

namespace Heritage\Tests\Pagination;

use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Pagination\CursorPaginator;
use Heritage\Tests\Pagination\Fixtures\Models\CursorResourceTestModel;
use LogicException;
use PHPUnit\Framework\TestCase;

class CursorResourceTest extends TestCase
{
    public function testItCanTransformToExplicitResource()
    {
        $paginator = new CursorResourceTestPaginator([
            new CursorResourceTestModel(),
        ], 1);

        $resource = $paginator->toResourceCollection(CursorResourceTestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectExceptionObject(new LogicException('Failed to find resource class for model [Heritage\Tests\Pagination\Fixtures\Models\CursorResourceTestModel].'));

        $paginator = new CursorResourceTestPaginator([
            new CursorResourceTestModel(),
        ], 1);

        $paginator->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $paginator = new CursorResourceTestPaginator([
            new CursorResourceTestModel(),
        ], 1);

        class_alias(CursorResourceTestResource::class, 'Heritage\Tests\Pagination\Fixtures\Http\Resources\CursorResourceTestModelResource');

        $resource = $paginator->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class CursorResourceTestResource extends JsonResource
{
    //
}

class CursorResourceTestPaginator extends CursorPaginator
{
    //
}
