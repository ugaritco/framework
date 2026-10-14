<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

/**
 * Why a flow step was skipped.
 */
enum SkipReason: string
{
    /** Its when() condition returned false. */
    case CONDITION_NOT_MET = 'condition_not_met';

    /** A step it needs failed. */
    case DEPENDENCY_FAILED = 'dependency_failed';

    /** A step it needs was skipped. */
    case DEPENDENCY_SKIPPED = 'dependency_skipped';
}
