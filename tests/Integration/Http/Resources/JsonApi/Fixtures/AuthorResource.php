<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Http\Request;
use Heritage\Http\Resources\JsonApi\JsonApiResource;

class AuthorResource extends JsonApiResource
{
    protected array $relationships = [
        'comments',
        'profile',
        'chaperonePosts' => PostResource::class,
    ];

    #[\Override]
    public function toAttributes(Request $request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
