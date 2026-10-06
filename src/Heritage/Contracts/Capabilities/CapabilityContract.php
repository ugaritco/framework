<?php

declare(strict_types=1);

namespace Heritage\Contracts\Capabilities;

use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;

/**
 * Interface CapabilityContract
 *
 * Core architectural contract for all execution capabilities in the Ugarit Ecosystem.
 *
 * Architectural Rules:
 * - A Capability is an atomic unit of execution responsible for performing a single,
 *   clearly defined operation (Single Responsibility Principle).
 * - It can operate on a single model or multiple models within an artifact or across the application.
 * - Must implement a handle() method that receives an optional Parcel and returns a structured Outcome.
 */
interface CapabilityContract
{
    /**
     * Execute the atomic capability business logic.
     *
     * @param  Parcel|ParcelContract|null  $data  Validated input Parcel (or null for parameterless operations).
     * @return Outcome The execution outcome envelope.
     */
    public function handle(Parcel|ParcelContract|null $data): Outcome;
}
