<?php

namespace Heritage\Tests\Database\Fixtures\Models;

use Heritage\Database\Eloquent\Attributes\UseResource;
use Heritage\Database\Eloquent\Model;
use Heritage\Tests\Database\Fixtures\Resources\EloquentResourceTestJsonResource;

#[UseResource(EloquentResourceTestJsonResource::class)]
class EloquentResourceTestResourceModelWithUseResourceAttribute extends Model
{
    //
}
