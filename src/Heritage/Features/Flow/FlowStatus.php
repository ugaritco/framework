<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

/**
 * The overall state of a flow, independent of its steps' states: a flow may complete
 * while an optional step failed.
 */
enum FlowStatus: string
{
    /** Defined, not run yet. */
    case PENDING = 'pending';

    /** Running its steps. */
    case RUNNING = 'running';

    /** Every step was evaluated and finish() built the outcome. */
    case COMPLETED = 'completed';

    /** Stopped by a required step's failure or an exception; the transaction was rolled back. */
    case HALTED = 'halted';
}
