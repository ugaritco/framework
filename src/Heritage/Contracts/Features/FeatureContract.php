<?php

declare(strict_types=1);

namespace Heritage\Contracts\Features;

use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;

/**
 * Interface FeatureContract
 *
 * Architectural contract for Feature workflow orchestrators in the Ugarit Ecosystem.
 *
 * Responsibilities:
 * - Coordinates across multiple atomic Capabilities, external services, or modules.
 * - Receives structured input via Parcels.
 * - Returns a unified execution Outcome.
 */
interface FeatureContract
{
    /**
     * Execute the feature orchestration workflow.
     *
     * @param  Parcel|ParcelContract|null  $data  Input Parcel boundary (or null for parameterless workflows).
     * @return Outcome The unified execution outcome envelope.
     */
    public function handle(Parcel|ParcelContract|null $data): Outcome;
}
