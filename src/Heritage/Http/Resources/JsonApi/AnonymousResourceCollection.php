<?php

namespace Heritage\Http\Resources\JsonApi;

use Heritage\Container\Container;
use Heritage\Http\JsonResponse;
use Heritage\Http\Request;
use Heritage\Http\Resources\Json\AnonymousResourceCollection as BaseAnonymousResourceCollection;
use Heritage\Support\Arr;

class AnonymousResourceCollection extends BaseAnonymousResourceCollection
{
    use Concerns\ResolvesJsonApiRequest;

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Heritage\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function with($request)
    {
        return array_filter([
            'included' => $this->collection
                ->map(fn ($resource) => $resource->resolveIncludedResourceObjects($request))
                ->flatten(depth: 1)
                ->uniqueStrict('_uniqueKey')
                ->map(fn ($included) => Arr::except($included, ['_uniqueKey']))
                ->values()
                ->all(),
            ...($implementation = JsonApiResource::$jsonApiInformation)
                ? ['jsonapi' => $implementation]
                : [],
        ]);
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Heritage\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function toAttributes(Request $request)
    {
        return $this->collection
            ->map(fn ($resource) => $resource->resolveResourceData($request))
            ->all();
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  \Heritage\Http\JsonResponse  $response
     * @return void
     */
    #[\Override]
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Heritage\Http\Request  $request
     * @return \Heritage\Http\JsonResponse
     */
    #[\Override]
    public function toResponse($request)
    {
        return parent::toResponse($this->resolveJsonApiRequestFrom($request));
    }

    /**
     * Resolve the HTTP request instance from container.
     *
     * @return \Heritage\Http\Resources\JsonApi\JsonApiRequest
     */
    #[\Override]
    protected function resolveRequestFromContainer()
    {
        return $this->resolveJsonApiRequestFrom(Container::getInstance()->make('request'));
    }
}
