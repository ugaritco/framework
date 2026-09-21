<?php

declare(strict_types=1);

namespace Heritage\Enums\Actions;

/**
 * Enum ActionType
 *
 * Defines the classification of actionable instructions returned to the frontend or caller.
 */
enum ActionType: string
{
    /**
     * Redirect the user interface to a specified URL.
     */
    case REDIRECT = 'redirect';

    /**
     * Dispatch a named event in the frontend application.
     */
    case EVENT = 'event';

    /**
     * Server-side executable callable callback action.
     */
    case CALLBACK = 'callback';
}
