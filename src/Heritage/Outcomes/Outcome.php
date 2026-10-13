<?php

declare(strict_types=1);

namespace Heritage\Outcomes;

use Heritage\Features\Flow\FlowState;
use Heritage\Responses\Outcome as BaseOutcome;

/**
 * Class Outcome
 *
 * Official execution outcome envelope in the Ugarit Ecosystem.
 * Represents the final result of any Capability or Feature execution.
 *
 * An outcome returned by a Flow carries its FlowState ($outcome->flow()); it is never
 * serialized (toArray(), JSON).
 */
readonly class Outcome extends BaseOutcome
{
    /**
     * The state of the flow that produced this outcome.
     */
    protected ?FlowState $flow;

    /**
     * The state of the flow that produced this outcome, if any.
     *
     * @return FlowState|null
     */
    public function flow(): ?FlowState
    {
        return isset($this->flow) ? $this->flow : null;
    }

    /**
     * Return a copy carrying the state of the flow that produced it.
     *
     * @param  FlowState  $state
     * @return static
     */
    public function withFlow(FlowState $state): static
    {
        return $this->copyWith(['flow' => $state]);
    }
}
