<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\Contracts\Responders\ResponderContract;
use Heritage\Contracts\Support\Arrayable;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Resources\Json\ResourceCollection;
use Heritage\Http\Responders\Options\ResponderOptions;
use Heritage\Responses\Outcome;
use InvalidArgumentException;

/**
 * Class Responder
 *
 * Base abstract responder providing unified helper methods for formatting and processing HTTP responses.
 *
 * Architectural Role:
 * 1. Transforms a domain Outcome returned by a Capability or Feature into an appropriate consumer Response (JSON, Inertia, Redirect).
 * 2. Decouples business logic from presentation and transport protocols.
 * 3. Provides centralized helpers to resolve API Resources, Collections, and Arrayable objects.
 * 4. Enforces pre-render existence checks for Inertia components across core pages and modular artifacts.
 */
abstract class Responder implements ResponderContract
{
    /**
     * Optional pre-bound ResponderOptions instance.
     *
     * @var ResponderOptions|null
     */
    protected ?ResponderOptions $options = null;

    /**
     * Initialize the Responder instance with optional pre-bound options.
     *
     * @param  ResponderOptions|null  $options  Optional responder options DTO.
     */
    public function __construct(?ResponderOptions $options = null)
    {
        $this->options = $options;
    }

    /**
     * Transform a domain Outcome + Options DTO into a transport-specific HTTP response.
     *
     * @param  ResponderOptions|Outcome|null  $options  Strongly-typed options object, pure Outcome, or null if pre-bound.
     * @return mixed Final HTTP response (JsonResponse, RedirectResponse, InertiaResponse, etc.).
     */
    abstract public function respond(ResponderOptions|Outcome|null $options = null): mixed;

    /**
     * Format raw outcome data into a standard structured array.
     *
     * Result structure:
     * [
     *     'success' => bool,
     *     'data' => resolved data,
     *     ... additional custom properties
     * ]
     *
     * @param  mixed  $outcome  The domain Outcome object returned by the operation.
     * @return array<string, mixed> Structured array ready for presentation.
     */
    protected function formatResponse(mixed $outcome): array
    {
        $data = method_exists($outcome, 'data') ? $outcome->data() : ($outcome->data ?? null);
        $success = method_exists($outcome, 'isSuccess') ? $outcome->isSuccess() : ($outcome->success ?? true);

        // Merge success status, resolved data, and custom public properties
        return array_merge(
            [
                // Determine success flag from domain outcome
                'success' => $success,

                // Resolve nested data (Resources, Collections, Arrayables)
                'data' => $this->resolveData($data),
            ],
            // Extract any additional custom public properties from the outcome object
            $this->extractProperties($outcome),
            // Feedback resolved from the Result and context (the properties only hold explicit overrides)
            $outcome instanceof Outcome ? [
                'message' => $outcome->message(),
                'title' => $outcome->title(),
                'variant' => $outcome->variant(),
                'statusCode' => $outcome->statusCode(),
            ] : []
        );
    }

    /**
     * Extract public properties from the outcome object, excluding internal and base properties.
     *
     * @param  mixed  $outcome  The outcome instance.
     * @return array<string, mixed>
     */
    protected function extractProperties(mixed $outcome): array
    {
        // Retrieve all public properties defined on the object
        $properties = get_object_vars($outcome);

        // Exclude reserved base properties already handled
        unset($properties['result'], $properties['data'], $properties['context'], $properties['errors'], $properties['actions']);

        // Resolve property values to ensure serializability
        return array_map(fn ($value) => $this->resolveData($value), $properties);
    }

    /**
     * Merge extra metadata into the response data array.
     *
     * @param  array<string, mixed>  $responseData  Base formatted response data.
     * @param  array<string, mixed>  $extra  Additional metadata passed from controller.
     * @return array<string, mixed> Merged array.
     */
    protected function mergeMeta(array $responseData, array $extra = []): array
    {
        // Merge extra metadata with response data
        return array_merge($responseData, $extra);
    }

    /**
     * Ensure that the given Inertia component exists among the pages of its application.
     *
     * Each application of the frontend (web, auth, panel) owns its pages and layouts under
     * resources/js/apps/{app}, so a page is named after its application:
     * 1. Application notation: "app/PagePath" (e.g. "panel/Dashboard", "auth/Login") is
     *    resources/js/apps/{app}/pages/{PagePath}.
     * 2. Artifact notation: "artifact::PagePath" (e.g. "i18n::language/Index") is a page of the
     *    artifact's panel, resources/js/apps/panel/artifacts/{artifact}/pages, or of its package,
     *    artifacts/{artifact}/resources/js/pages.
     * A frontend without resources/js/apps keeps its pages in resources/js/pages.
     *
     * @param  string  $component  The component identifier (e.g. "panel/Dashboard" or "i18n::language/Index").
     * @return void
     *
     * @throws InvalidArgumentException If the component file does not exist in its pages directory.
     */
    protected function ensureComponentExists(string $component): void
    {
        if (str_contains($component, '::')) {
            [$artifact, $path] = explode('::', $component, 2);
            $directories = [
                resource_path("js/apps/panel/artifacts/{$artifact}/pages"),
                base_path("artifacts/{$artifact}/resources/js/pages"),
            ];
        } elseif (is_dir(resource_path('js/apps'))) {
            [$app, $path] = array_pad(explode('/', $component, 2), 2, '');
            $directories = [resource_path("js/apps/{$app}/pages")];
        } else {
            $path = $component;
            $directories = [resource_path('js/pages')];
        }

        $segments = explode('/', $path);
        $segments[] = ucfirst((string) array_pop($segments));
        $candidates = array_unique([$path, implode('/', $segments)]);

        foreach ($directories as $directory) {
            foreach ($candidates as $candidate) {
                foreach (['vue', 'jsx', 'tsx', 'svelte'] as $extension) {
                    if (is_file("{$directory}/{$candidate}.{$extension}")) {
                        return;
                    }
                }
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Inertia component [%s] was not found in [%s]; a page is named after its application ("panel/Dashboard") or artifact ("i18n::language/Index").',
            $component,
            implode('], [', $directories)
        ));
    }

    /**
     * Resolve raw data into a frontend-safe format.
     *
     * @param  mixed  $data  Raw data from the outcome envelope.
     * @return mixed Resolved data ready for serialization.
     */
    protected function resolveData(mixed $data): mixed
    {
        // Resolve JsonResource or ResourceCollection instances
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve();
        }

        // Convert Arrayable instances to native arrays
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if (is_array($data)) {
            return array_map(fn ($value) => $this->resolveData($value), $data);
        }

        // Return primitives or arrays as-is
        return $data;
    }
}
