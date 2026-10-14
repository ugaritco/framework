<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

/**
 * The execution state of a flow step (or of one item of an each() step).
 */
enum StepStatus: string
{
    /** Not evaluated yet; steps after a halting failure stay pending. */
    case PENDING = 'pending';

    /** Ran and succeeded; its result is available by name. */
    case SUCCEEDED = 'succeeded';

    /** Ran and failed (or raised an exception). */
    case FAILED = 'failed';

    /** Not run: its condition or a dependency was not met (see SkipReason). */
    case SKIPPED = 'skipped';
}
