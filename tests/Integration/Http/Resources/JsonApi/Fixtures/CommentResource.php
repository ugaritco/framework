<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Http\Resources\JsonApi\JsonApiResource;

class CommentResource extends JsonApiResource
{
    /**
     * The resource's attributes.
     */
    public $attributes = [
        'content',
    ];

    /**
     * The resource's relationships.
     */
    public $relationships = [
        'posts',
        'commenter' => UserResource::class,
    ];
}
