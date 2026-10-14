<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

use RuntimeException;

/**
 * Raised inside a flow when a required step fails, to roll its transaction back.
 *
 * @internal Caught by Flow; never leaves it.
 */
final class FlowHalted extends RuntimeException
{
    /**
     * @param  StepRecord  $record  The failed record (an each() item's record for an each() step).
     */
    public function __construct(public readonly StepRecord $record)
    {
        parent::__construct("Flow halted by step [{$record->name}].");
    }
}
