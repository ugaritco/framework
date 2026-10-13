<?php

declare(strict_types=1);

namespace Heritage\Capabilities;

use RuntimeException;
use Throwable;

/**
 * Class CapabilityException
 *
 * An expected domain failure thrown from a capability's perform(): not a bug, so it is not
 * reported. Capability::handle() returns failed() with the exception's errors, which are
 * safe to display (they are written for the user, unlike the details of other exceptions).
 */
class CapabilityException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $errors  Displayable failure reasons, keyed like validation errors.
     * @param  string  $message  Developer-facing description.
     * @param  Throwable|null  $previous  The underlying exception, if any.
     */
    public function __construct(
        protected array $errors = [],
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * The displayable failure reasons.
     *
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
