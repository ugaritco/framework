<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\Http\RedirectResponse;
use Heritage\Http\Responders\Options\RedirectToRouteResponderOptions;
use Heritage\Http\Responders\Options\ResponderOptions;
use Heritage\Responses\Outcome;
use InvalidArgumentException;

/**
 * Class RedirectToRouteResponder
 *
 * Specialized HTTP responder for redirecting to a named route with parameters and flash messages based on Outcome.
 *
 * Features:
 * 1. Automatically flashes toast notifications to the session for display on the destination page.
 * 2. Flashes custom data into the session.
 * 3. Safely passes route parameters to the redirect()->route() helper.
 */
class RedirectToRouteResponder extends Responder
{
    /**
     * Transform options into an HTTP RedirectResponse pointing to a named route.
     *
     * @param  ResponderOptions|Outcome|null  $options  Instance of RedirectToRouteResponderOptions or pure Outcome.
     * @param  mixed  ...$extraArgs  Optional routeName and parameters if Outcome was passed directly.
     * @return RedirectResponse
     *
     * @throws InvalidArgumentException If options is not an instance of RedirectToRouteResponderOptions or Outcome.
     */
    public function respond(ResponderOptions|Outcome|null $options = null, ...$extraArgs): RedirectResponse
    {
        // Resolve provided options or fall back to pre-bound instance
        $options = $options ?? $this->options;

        // Step 1: Wrap direct Outcome into RedirectToRouteResponderOptions
        if ($options instanceof Outcome) {
            $options = new RedirectToRouteResponderOptions(
                outcome: $options,
                routeName: $extraArgs[0] ?? '',
                parameters: $extraArgs[1] ?? []
            );
        }

        // Step 2: Validate options instance type
        if (! $options instanceof RedirectToRouteResponderOptions) {
            throw new InvalidArgumentException(
                'Expected instance of RedirectToRouteResponderOptions or Outcome'
            );
        }

        // Step 2: Extract base domain outcome
        $outcome = $options->outcome;

        // Step 3: Flash toast to session for next request
        $outcome->toast()?->flash();

        // Step 4: Flash any additional custom data to session
        foreach ($options->flash as $key => $value) {
            session()->flash($key, $value);
        }

        // Step 5: Generate and return redirect response to target route with parameters
        return redirect()->route($options->routeName, $options->parameters);
    }
}
