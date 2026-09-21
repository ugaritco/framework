<?php

declare(strict_types=1);

namespace Heritage\Factories;

use Heritage\Enums\Responders\ResponderType;
use Heritage\Http\Responders\InertiaResponder;
use Heritage\Http\Responders\JsonResponder;
use Heritage\Http\Responders\RedirectBackResponder;
use Heritage\Http\Responders\RedirectToRouteResponder;
use Heritage\Http\Responders\Responder;

/**
 * Class ResponderFactory
 *
 * Factory responsible for creating explicit HTTP Responder instances based on the requested format.
 *
 * Architectural Philosophy:
 * - Complete decoupling of domain business logic from the transport/presentation layer.
 * - A UseCase returns a pure Response object unaware of how it will be delivered.
 * - The Controller specifies the ResponderType, and this factory instantiates the specialized Responder.
 */
class ResponderFactory
{
    /**
     * Create and return a specialized Responder instance matching the given ResponderType.
     *
     * @param  ResponderType  $type  The type of responder (JSON, INERTIA, REDIRECT_BACK, REDIRECT_TO_ROUTE).
     * @return Responder Concrete Responder instance extending the base Responder class.
     */
    public function make(ResponderType $type): Responder
    {
        // Match the responder type and return the corresponding specialized responder instance
        return match ($type) {
            // Standard JSON responder for API requests and mobile clients
            ResponderType::JSON => new JsonResponder,

            // Inertia.js responder for modern Single Page Application (SPA) views
            ResponderType::INERTIA => new InertiaResponder,

            // Redirect back responder with session flash messages and toast notifications
            ResponderType::REDIRECT_BACK => new RedirectBackResponder,

            // Redirect to a specific named route with parameters and flash messages
            ResponderType::REDIRECT_TO_ROUTE => new RedirectToRouteResponder,
        };
    }
}
