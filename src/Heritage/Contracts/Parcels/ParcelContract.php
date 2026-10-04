<?php

declare(strict_types=1);

namespace Heritage\Contracts\Parcels;

use Heritage\Database\Eloquent\Model;

/**
 * Interface ParcelContract
 *
 * Core architectural contract for all structured data envelopes (Parcels) in the Ugarit Ecosystem.
 *
 * Architectural Rules:
 * - A Parcel represents a strongly-typed, validated data envelope that securely transports data across layers.
 * - Enforces boundary integrity before execution in Capabilities and Features.
 * - Provides bidirectional serialization to/from arrays and Eloquent models.
 */
interface ParcelContract
{
    /**
     * Convert the Parcel properties into an associative array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Convert the Parcel to a JSON string.
     *
     * @param  int  $options  json_encode options.
     * @return string
     */
    public function toJson(int $options = 0): string;

    /**
     * Attach an Eloquent model instance to this Parcel.
     *
     * @param  Model  $model
     * @return static
     */
    public function setModel(Model $model): static;

    /**
     * Retrieve the attached Eloquent model instance if available.
     *
     * @return Model|null
     */
    public function getModel(): ?Model;

    /**
     * Determine if null values should be filtered out before persisting to database.
     *
     * @return bool
     */
    public function shouldFilterNulls(): bool;
}
