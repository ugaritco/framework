<?php

declare(strict_types=1);

namespace Heritage\Features;

use Heritage\Contracts\Features\FeatureContract;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;
use Heritage\Traits\Concerns\HasTransaction;

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
 * - Uses the HasTransaction trait to provide cross-domain database transaction management when needed.
 *
 * @template TInput of Parcel
 */
abstract class Feature implements FeatureContract
{
    use HasTransaction;

    /**
     * Execute the high-level feature workflow coordinating across multiple Capabilities.
     *
     * @param  Parcel|ParcelContract|null  $data  Input Parcel boundary (or null for parameterless workflows).
     * @return Outcome The unified outcome envelope of the feature execution.
     */
    abstract public function handle(Parcel|ParcelContract|null $data): Outcome;
}
