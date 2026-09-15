<?php

namespace Heritage\Tests\Database\Fixtures\Models;

use Heritage\Database\Eloquent\Attributes\UseResourceCollection;
use Heritage\Database\Eloquent\Model;
use Heritage\Tests\Database\Fixtures\Resources\EloquentResourceTestJsonResourceCollection;

#[UseResourceCollection(EloquentResourceTestJsonResourceCollection::class)]
class EloquentResourceTestResourceModelWithUseResourceCollectionAttribute extends Model
{
    //
}
