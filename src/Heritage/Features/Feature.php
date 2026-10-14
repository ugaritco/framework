<?php

declare(strict_types=1);

namespace Heritage\Features;

use Heritage\Contracts\Features\FeatureContract;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Contracts\Result\ResultContract;
use Heritage\Features\Flow\Flow;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;
use TypeError;

/**
 * Class Feature
 *
 * Base abstract class for application Features.
 *
 * Architectural Role & Hierarchy:
 * - Positioned at the top of the domain workflow hierarchy (Feature -> Capabilities).
 * - Responsible for executing comprehensive business workflows that coordinate across MULTIPLE Capabilities
 *   spanning one or multiple artifacts.
 * - Example: A SaveBranchFeature coordinates SaveBranchCapability, SaveBranchTranslationCapability,
 *   and DestroyBranchTranslationCapability within a database transaction.
 * - Describes its workflow with flow(): named steps running capabilities in order inside one
 *   transaction, with optional steps, dependencies (needs) and conditions (when). The flow is
 *   the feature's only transaction and failure handling: it rolls back, reports, and builds
 *   the failure outcome.
 *
 * A feature implements perform(), whose parameter type declares the accepted Parcel, as in a
 * capability; PHP enforces it:
 *
 *     protected function perform(SaveBranchParcel $data): Outcome
 *
 * @method Outcome perform(mixed $data = null) Execute the workflow.
 */
abstract class Feature implements FeatureContract
{
    /**
     * Execute the feature by running perform().
     *
     * @param  Parcel|ParcelContract|null  $data  Input Parcel boundary (or null for parameterless workflows).
     * @return Outcome The unified outcome envelope of the feature execution.
     *
     * @throws TypeError When perform() does not accept the input.
     */
    final public function handle(Parcel|ParcelContract|null $data = null): Outcome
    {
        return $this->perform($data);
    }

    /**
     * Start the feature's workflow: define its steps, then finish() runs them.
     *
     * @param  class-string<Outcome>|null  $outcome  The feature's Outcome: a halted flow returns it, built
     *                                               from the failure's result with its errors, without onFailure().
     * @param  ResultContract|null  $failWith  The failure result of steps without their own failWith.
     * @return Flow
     */
    protected function flow(?string $outcome = null, ?ResultContract $failWith = null): Flow
    {
        return new Flow($outcome, $failWith);
    }
}
