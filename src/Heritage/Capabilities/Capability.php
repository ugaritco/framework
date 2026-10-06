<?php

declare(strict_types=1);

namespace Heritage\Capabilities;

use Heritage\Contracts\Capabilities\CapabilityContract;
use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Factories\RepositoryFactory;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;

/**
 * Class Capability
 *
 * Base abstract class for execution capabilities in the Ugarit Ecosystem.
 *
 * Architectural Role & Hierarchy:
 * - A Capability represents an execution unit that carries out a single, clearly defined operation.
 * - It can interact with one or multiple models, at the module/artifact or application level.
 * - Encapsulates atomic persistence, mutation, query, or computational logic.
 * - Coordinates with repositories via dependency inversion.
 *
 * @template TInput of Parcel
 */
abstract class Capability implements CapabilityContract
{
    /**
     * The resolved repository instance associated with this Capability.
     *
     * @var mixed
     */
    protected mixed $repository = null;

    /**
     * The RepositoryFactory instance used to dynamically resolve repository contracts.
     *
     * @var RepositoryFactory
     */
    protected RepositoryFactory $factory;

    /**
     * Initialize the Capability instance with the RepositoryFactory dependency.
     *
     * @param  RepositoryFactory|null  $factory  The repository factory instance (optional).
     */
    public function __construct(?RepositoryFactory $factory = null)
    {
        // Store the factory instance or dynamically resolve from container / instantiate
        $this->factory = $factory ?? (function_exists('app') ? app(RepositoryFactory::class) : new RepositoryFactory());
    }

    /**
     * Dynamically resolve and bind the repository for this Capability using its interface contract.
     *
     * @param  string  $interface  Fully qualified repository interface name (e.g. BranchRepositoryInterface::class).
     * @return void
     *
     * @throws BindingResolutionException If the container fails to resolve the interface.
     */
    protected function setRepository(string $interface): void
    {
        // Delegate to RepositoryFactory to resolve the concrete repository and assign it
        $this->repository = $this->factory->make($interface);
    }

    /**
     * Retrieve the resolved repository instance bound to this Capability.
     *
     * @return mixed The resolved repository instance.
     */
    public function getRepository(): mixed
    {
        // Return the bound repository instance
        return $this->repository;
    }

    /**
     * Execute the atomic operation for this capability.
     *
     * @param  Parcel|ParcelContract|null  $data  Input Parcel (or null for parameterless operations).
     * @return Outcome Structured outcome envelope containing result status and payload.
     */
    abstract public function handle(Parcel|ParcelContract|null $data): Outcome;
}
