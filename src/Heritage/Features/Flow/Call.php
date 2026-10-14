<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

use Heritage\Contracts\Capabilities\CapabilityContract;
use Heritage\Contracts\Features\FeatureContract;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Contracts\Result\ResultContract;
use Heritage\Parcels\Parcel;

/**
 * What one item of an each() step runs: a capability (or feature) with its parcel.
 *
 *     ->each('translations', $translations, fn ($attributes, $locale) => Call::to(SaveTranslationCapability::class, $parcel))
 */
final class Call
{
    /**
     * The failure result of this call, overriding the step's failWith.
     */
    public ?ResultContract $failWith = null;

    /**
     * @param  class-string<CapabilityContract|FeatureContract>|CapabilityContract|FeatureContract  $target  What to run.
     * @param  Parcel|ParcelContract|null  $parcel  Its input.
     */
    public function __construct(
        public readonly string|CapabilityContract|FeatureContract $target,
        public readonly Parcel|ParcelContract|null $parcel = null,
    ) {}

    /**
     * Run a capability (or feature) with a parcel.
     *
     * @param  class-string<CapabilityContract|FeatureContract>|CapabilityContract|FeatureContract  $target  What to run.
     * @param  Parcel|ParcelContract|null  $parcel  Its input.
     * @return self
     */
    public static function to(string|CapabilityContract|FeatureContract $target, Parcel|ParcelContract|null $parcel = null): self
    {
        return new self($target, $parcel);
    }

    /**
     * The failure result when this call fails, overriding the step's failWith.
     *
     * @param  ResultContract  $result
     * @return $this
     */
    public function failWith(ResultContract $result): self
    {
        $this->failWith = $result;

        return $this;
    }
}
