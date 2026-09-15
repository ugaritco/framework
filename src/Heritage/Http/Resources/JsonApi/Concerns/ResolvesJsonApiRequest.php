<?php

namespace Heritage\Http\Resources\JsonApi\Concerns;

use Heritage\Http\Request;
use Heritage\Http\Resources\JsonApi\JsonApiRequest;

trait ResolvesJsonApiRequest
{
    /**
     * Resolve a JSON API request instance from the given HTTP request.
     *
     * @return \Heritage\Http\Resources\JsonApi\JsonApiRequest
     */
    protected function resolveJsonApiRequestFrom(Request $request)
    {
        return $request instanceof JsonApiRequest
            ? $request
            : JsonApiRequest::createFrom($request);
    }
}
