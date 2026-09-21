<?php

declare(strict_types=1);

namespace Heritage\Enums\Actions\Feedback;

/**
 * Enum Variant
 *
 * Categorizes visual feedback variants for UI presentation and styling.
 */
enum Variant: string
{
    /**
     * Success variant (typically green).
     * Indicates successful completion of an operation.
     */
    case SUCCESS = 'success';

    /**
     * Error variant (typically red).
     * Indicates failure or an unrecoverable error condition.
     */
    case ERROR = 'error';

    /**
     * Warning variant (typically yellow or amber).
     * Indicates a cautionary situation requiring user attention.
     */
    case WARNING = 'warning';

    /**
     * Informational variant (typically blue).
     * Indicates neutral informative messages without error or success implications.
     */
    case INFO = 'info';
}
