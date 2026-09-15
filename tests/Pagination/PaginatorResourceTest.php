<?php

namespace Heritage\Tests\Pagination;

use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Pagination\LengthAwarePaginator;
use Heritage\Tests\Pagination\Fixtures\Models\PaginatorResourceTestModel;
use LogicException;
use PHPUnit\Framework\TestCase;

class PaginatorResourceTest extends TestCase
{
    public function testItCanTransformToExplicitResource()
    {
        $paginator = new PaginatorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1, 1, 1);

        $resource = $paginator->toResourceCollection(PaginatorResourceTestResource::class);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    public function testItThrowsExceptionWhenResourceCannotBeFound()
    {
        $this->expectExceptionObject(new LogicException('Failed to find resource class for model [Heritage\Tests\Pagination\Fixtures\Models\PaginatorResourceTestModel].'));

        $paginator = new PaginatorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1, 1, 1);

        $paginator->toResourceCollection();
    }

    public function testItCanGuessResourceWhenNotProvided()
    {
        $paginator = new PaginatorResourceTestPaginator([
            new PaginatorResourceTestModel(),
        ], 1, 1, 1);

        class_alias(PaginatorResourceTestResource::class, 'Heritage\Tests\Pagination\Fixtures\Http\Resources\PaginatorResourceTestModelResource');

        $resource = $paginator->toResourceCollection();

        $this->assertInstanceOf(JsonResource::class, $resource);
    }
}

class PaginatorResourceTestResource extends JsonResource
{
    //
}

class PaginatorResourceTestPaginator extends LengthAwarePaginator
{
    //
}
