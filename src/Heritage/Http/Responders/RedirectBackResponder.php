<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\Http\RedirectResponse;
use Heritage\Http\Responders\Options\RedirectBackResponderOptions;
use Heritage\Http\Responders\Options\ResponderOptions;
use InvalidArgumentException;

/**
 * Class RedirectBackResponder
 *
 * Specialized HTTP responder for redirecting back with toast and session flash data based on Outcome.
 *
 * Features:
 * 1. Automatically flashes toast notifications to the session for immediate display upon redirect.
 * 2. Flashes any additional custom session data.
 * 3. On failure, redirects back with form errors (withErrors) and preserves old input (withInput).
 * 4. On success, executes a clean redirect back.
 */
class RedirectBackResponder extends Responder
{
    /**
     * Transform options into an HTTP RedirectResponse going back to the previous URL.
     *
     * @param  ResponderOptions  $options  Must be an instance of RedirectBackResponderOptions.
     * @return RedirectResponse
     *
     * @throws InvalidArgumentException If options is not an instance of RedirectBackResponderOptions.
     */
    public function respond(?ResponderOptions $options = null): RedirectResponse
    {
        // Resolve provided options or fall back to pre-bound instance
        $options = $options ?? $this->options;

        // Step 1: Validate options instance type
        if (! $options instanceof RedirectBackResponderOptions) {
            throw new InvalidArgumentException(
                'Expected instance of RedirectBackResponderOptions'
            );
        }

        // Step 2: Extract base domain outcome
        $outcome = $options->outcome;

        // Step 3: Flash toast notification to session for next request
        $outcome->toast()?->flash();

        // Step 4: Flash any custom key-value pairs specified in options
        foreach ($options->flash as $key => $value) {
            session()->flash($key, $value);
        }

        // Step 5: Handle failure vs success redirects
        if (! $outcome->isSuccess()) {
            // On failure: redirect back with form errors and preserve old input
            return back()->withErrors([
                'form' => $outcome->message(),
            ])->withInput();
        }

        // On success: redirect back cleanly
        return back();
    }
}
