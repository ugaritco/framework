<?php

declare(strict_types=1);

namespace Heritage\Enums\Responders;

/**
 * Enum ResponderType
 *
 * Defines the supported HTTP response channels for presentation layers.
 */
enum ResponderType: string
{
    /**
     * Standard JSON HTTP response for REST APIs and headless clients.
     */
    case JSON = 'json';

    /**
     * Modern SPA page render via Inertia.js protocol.
     */
    case INERTIA = 'inertia';

    /**
     * HTTP redirect back to the previous URL with session flash feedback.
     */
    case REDIRECT_BACK = 'redirect_back';

    /**
     * HTTP redirect to a named route with parameters and flash feedback.
     */
    case REDIRECT_TO_ROUTE = 'redirect_to_route';
}
