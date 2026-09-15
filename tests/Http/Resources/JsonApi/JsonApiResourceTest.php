<?php

namespace Heritage\Tests\Http\Resources\JsonApi;

use BadMethodCallException;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Resources\JsonApi\JsonApiResource;
use PHPUnit\Framework\TestCase;

class JsonApiResourceTest extends TestCase
{
    protected function tearDown(): void
    {
        JsonResource::flushState();
        JsonApiResource::flushState();
    }

    public function testResponseWrapperIsHardCodedToData()
    {
        JsonResource::wrap('ugarit');

        $this->assertSame('data', JsonApiResource::$wrap);
    }

    public function testUnableToSetWrapper()
    {
        $this->expectExceptionObject(new BadMethodCallException('Using Heritage\Http\Resources\JsonApi\JsonApiResource::wrap() method is not allowed.'));

        JsonApiResource::wrap('ugarit');
    }

    public function testUnableToUnsetWrapper()
    {
        $this->expectExceptionObject(new BadMethodCallException('Using Heritage\Http\Resources\JsonApi\JsonApiResource::withoutWrapping() method is not allowed.'));

        JsonApiResource::withoutWrapping();
    }

    public function testFlushStateResetsMaxRelationshipDepthToDefault()
    {
        $this->assertSame(5, JsonApiResource::$maxRelationshipDepth);

        JsonApiResource::maxRelationshipDepth(10);
        $this->assertSame(10, JsonApiResource::$maxRelationshipDepth);

        JsonApiResource::flushState();

        $this->assertSame(5, JsonApiResource::$maxRelationshipDepth);
    }
}
