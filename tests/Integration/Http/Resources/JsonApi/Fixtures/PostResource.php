<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Http\Request;
use Heritage\Http\Resources\JsonApi\JsonApiResource;

class PostResource extends JsonApiResource
{
    /**
     * The number of times the "comments" relationship closure has been resolved.
     */
    public static int $commentsResolutionCount = 0;

    protected array $attributes = [
        'title',
        'content',
    ];

    #[\Override]
    public function toRelationships(Request $request)
    {
        return [
            'author' => AuthorResource::class,
            'comments' => function () {
                static::$commentsResolutionCount++;

                return CommentResource::collection(
                    $this->comments->where('content', '!=', 'private')
                );
            },
        ];
    }
}
