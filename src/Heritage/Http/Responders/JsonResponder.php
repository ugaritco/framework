<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\Http\JsonResponse;
use Heritage\Http\Responders\Options\JsonResponderOptions;
use Heritage\Http\Responders\Options\ResponderOptions;
use Heritage\Responses\Outcome;

/**
 * Class JsonResponder
 *
 * Specialized HTTP responder handling API requests and returning standardized JSON responses.
 *
 * Features:
 * 1. Transforms domain Outcome objects into a structured JSON envelope with success flag, data payload, and metadata.
 * 2. Resolves HTTP status codes automatically from the Outcome or supports explicit override.
 * 3. Supports direct invocation with Outcome objects or via strongly-typed JsonResponderOptions DTOs.
 */
class JsonResponder extends Responder
{
    /**
     * Return a standardized JSON response.
     *
     * @param  ResponderOptions|Outcome|null  $options  Options DTO, domain Outcome instance, or null if pre-bound.
     * @param  mixed  ...$extraArgs  Optional parameters: [0] => HTTP status code, [1] => extra metadata array.
     * @return JsonResponse The finalized JSON HTTP response.
     *
     * @throws InvalidArgumentException If no options or outcome are provided.
     */
    public function respond(ResponderOptions|Outcome|null $options = null, ...$extraArgs): JsonResponse
    {
        // Resolve provided options or fall back to pre-bound instance
        $options = $options ?? $this->options;

        if ($options === null) {
            throw new InvalidArgumentException('No ResponderOptions or Outcome provided to JsonResponder.');
        }
        // Case 1: Direct domain Outcome object passed without Options wrapper
        if ($options instanceof Outcome) {
            // Determine status code from extra arguments or fallback to outcome default
            $status = $extraArgs[0] ?? $options->statusCode();

            // Extract additional metadata if provided
            $extra = $extraArgs[1] ?? [];

            // Merge metadata with formatted outcome data
            $data = $this->mergeMeta($this->formatResponse($options), $extra);

            // Construct and return JSON response
            return $this->createJsonResponse($data, $status);
        }

        // Case 2: Strongly-typed ResponderOptions or JsonResponderOptions DTO
        $outcome = $options->outcome;

        // Determine HTTP status code, prioritizing explicit status in JsonResponderOptions
        $status = ($options instanceof JsonResponderOptions && $options->status !== null)
            ? $options->status
            : $outcome->statusCode();

        // Merge formatted outcome data with extra options
        $data = $this->mergeMeta($this->formatResponse($outcome), $options->extra);

        // Return the finalized JSON response with appropriate status code
        return $this->createJsonResponse($data, $status);
    }

    /**
     * Create the final JsonResponse instance using ResponseFactory if available or direct instantiation.
     *
     * @param  array<string, mixed>  $data  The payload data.
     * @param  int  $status  The HTTP status code.
     * @return JsonResponse The HTTP JSON response instance.
     */
    protected function createJsonResponse(array $data, int $status): JsonResponse
    {
        // Use container response factory if bound in the application
        if (function_exists('app') && app()->bound(\Heritage\Contracts\Routing\ResponseFactory::class)) {
            return response()->json($data, $status);
        }

        // Fallback to direct HTTP JsonResponse instantiation
        return new JsonResponse($data, $status);
    }
}
