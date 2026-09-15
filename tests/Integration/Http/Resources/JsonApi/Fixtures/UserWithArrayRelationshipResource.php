<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Http\Request;
use Heritage\Http\Resources\JsonApi\JsonApiResource;

class UserWithArrayRelationshipResource extends JsonApiResource
{
    public function toType(Request $request)
    {
        return 'users';
    }

    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
