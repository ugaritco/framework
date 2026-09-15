<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Database\Eloquent\Attributes\UseFactory;
use Heritage\Database\Eloquent\Attributes\UseResource;
use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Database\Eloquent\Model;

#[UseResource(ProfileResource::class)]
#[UseFactory(ProfileFactory::class)]
class Profile extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
