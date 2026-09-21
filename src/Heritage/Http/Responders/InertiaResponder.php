<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\Http\Responders\Options\InertiaResponderOptions;
use Heritage\Http\Responders\Options\ResponderOptions;
use Inertia\Inertia;
use InvalidArgumentException;
use LogicException;

/**
 * Class InertiaResponder
 *
 * Specialized HTTP responder converting domain Outcomes into Inertia.js views for modern SPAs.
 *
 * Features:
 * 1. Verifies component existence in project or artifact pages prior to rendering.
 * 2. Automatically shares toast feedback notifications with Inertia via Inertia::share and session flash.
 * 3. Passes resolved data payloads and extra metadata into component props.
 */
class InertiaResponder extends Responder
{
    /**
     * Transform options into an Inertia HTTP response.
     *
     * @param  ResponderOptions  $options  Must be an instance of InertiaResponderOptions.
     * @return mixed Inertia response object.
     *
     * @throws InvalidArgumentException If options is not an instance of InertiaResponderOptions.
     * @throws LogicException If the Inertia package is not installed.
     */
    public function respond(?ResponderOptions $options = null): mixed
    {
        // Resolve provided options or fall back to pre-bound instance
        $options = $options ?? $this->options;

        // Step 1: Type check options instance
        if (! $options instanceof InertiaResponderOptions) {
            throw new InvalidArgumentException(
                'Expected instance of InertiaResponderOptions'
            );
        }

        // Step 2: Ensure Inertia is installed in the environment
        if (! class_exists(Inertia::class)) {
            throw new LogicException('Inertia package is not installed.');
        }

        // Step 3: Validate component existence before rendering to prevent client errors
        $this->ensureComponentExists($options->component);

        // Step 4: Extract domain outcome and format data
        $outcome = $options->outcome;
        $responseData = $this->formatResponse($outcome);

        // Step 5: Share toast feedback with Inertia session for frontend components
        if ($toast = $outcome->toast()) {
            // Retrieve currently shared flash data
            $existingFlash = Inertia::getShared('flash') ?? [];

            // Share toast if no existing toast is present or if the operation resulted in failure
            if (! isset($existingFlash['toast']) || ! $outcome->isSuccess()) {
                Inertia::share('flash', array_merge((array) $existingFlash, [
                    'toast' => $toast->toArray(),
                ]));
            }
        }

        // Step 6: Merge formatted response data with extra metadata
        $data = $this->mergeMeta($responseData, $options->extra);

        // Step 7: Render Inertia component with props and return response
        return Inertia::render(
            component: $options->component,
            props: $data
        );
    }
}
