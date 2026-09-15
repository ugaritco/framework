<?php

namespace Heritage\Tests\Integration\Http\Fixtures;

use Heritage\Http\Resources\Json\ResourceCollection;

class EmptyPostCollectionResource extends ResourceCollection
{
    public $collects = PostResource::class;
}
