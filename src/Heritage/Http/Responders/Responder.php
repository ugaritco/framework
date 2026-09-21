<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\Contracts\Support\Arrayable;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Resources\Json\ResourceCollection;
use Heritage\Http\Responders\Options\ResponderOptions;
use InvalidArgumentException;

/**
 * Class Responder
 *
 * Base abstract responder providing unified helper methods for formatting and processing HTTP responses.
 *
 * Architectural Role:
 * 1. Transforms a domain Outcome returned by a UseCase, Service, or Feature along with its
 *    strongly-typed ResponderOptions into an appropriate transport-level HTTP response (JSON, Inertia, Redirect).
 * 2. Provides centralized helpers to resolve API Resources, Collections, and Arrayable objects.
 * 3. Enforces pre-render existence checks for Inertia components across core pages and modular artifacts.
 */
abstract class Responder
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
     * @param  ResponderOptions|null  $options  Strongly-typed options object (optional if pre-bound).
     * @return mixed Final HTTP response (JsonResponse, RedirectResponse, InertiaResponse, etc.).
     */
    abstract public function respond(?ResponderOptions $options = null): mixed;

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
            $this->extractProperties($outcome)
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
     * Ensure that the given Inertia component exists in the application or artifact pages directory.
     *
     * Supported notations:
     * 1. Artifact notation: "artifact::PagePath" (e.g., "i18n::Languages/Index").
     * 2. Core project notation: "PagePath" (e.g., "Dashboard/Index").
     *
     * @param  string  $component  The component identifier (e.g. "Languages/Index" or "i18n::Languages/Index").
     * @return void
     *
     * @throws InvalidArgumentException If the component file does not exist in any supported search path.
     */
    protected function ensureComponentExists(string $component): void
    {
        // Supported frontend component file extensions in Ugarit
        $extensions = ['vue', 'jsx', 'tsx', 'svelte'];

        // Case 1: Artifact scoped component notation ("artifact::path")
        if (str_contains($component, '::')) {
            // Split artifact slug from relative page path
            [$artifact, $path] = explode('::', $component, 2);

            // Candidate search paths for artifact pages
            $searchPaths = [
                // Integrated project artifact assets
                resource_path("js/artifacts/{$artifact}/pages/{$path}"),
                resource_path("js/artifacts/{$artifact}/Pages/{$path}"),
                // Standalone package artifact resources
                base_path("artifacts/{$artifact}/resources/js/pages/{$path}"),
                base_path("artifacts/{$artifact}/resources/js/Pages/{$path}"),
                // Panel module resources
                resource_path("js/apps/panel/modules/{$artifact}/pages/{$path}"),
                base_path("modules/{$artifact}/resources/js/pages/{$path}"),
            ];

            // Search for file existence across all extensions
            foreach ($searchPaths as $searchPath) {
                foreach ($extensions as $ext) {
                    if (is_file("{$searchPath}.{$ext}")) {
                        // Component verified successfully
                        return;
                    }
                }
            }

            // Component was not found in any artifact directory
            throw new InvalidArgumentException(
                "Inertia component [{$component}] was not found in artifact or module pages."
            );
        }

        // Case 2: Central project pages
        $projectPagePaths = [
            // Standard default pages
            resource_path("js/Pages/{$component}"),
            resource_path("js/pages/{$component}"),
            // Multi-app project paths
            resource_path("js/apps/auth/pages/{$component}"),
            resource_path("js/apps/app/pages/{$component}"),
            resource_path("js/apps/panel/pages/{$component}"),
            resource_path("js/apps/panel/Pages/{$component}"),
        ];

        // Search for file existence in project directories
        foreach ($projectPagePaths as $projectPagePath) {
            foreach ($extensions as $ext) {
                if (is_file("{$projectPagePath}.{$ext}")) {
                    // Component verified successfully
                    return;
                }
            }
        }

        // Throw descriptive exception to prevent frontend runtime render failures
        throw new InvalidArgumentException(
            "Inertia component [{$component}] was not found in Project Pages."
        );
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

        // Return primitives or arrays as-is
        return $data;
    }
}
