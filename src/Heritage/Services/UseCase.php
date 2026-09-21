<?php

declare(strict_types=1);

namespace Heritage\Services;

use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\UseCases\UseCaseContract;
use Heritage\DTOs\DTO;
use Heritage\Factories\RepositoryFactory;
use Heritage\Responses\Outcome;

/**
 * Class UseCase
 *
 * Base abstract class for atomic application UseCases.
 *
 * Architectural Role & Hierarchy:
 * - A UseCase is the smallest atomic unit of work in the system.
 * - It is strictly responsible for recording, mutating, or querying a SINGLE entity/record in the system.
 * - For operations requiring multiple record updates (e.g. creating a record AND its translation),
 *   dedicated UseCases must be composed by a Service.
 * - Interacts with persistence layers strictly via a repository contract.
 *
 * @template TInput of DTO
 */
abstract class UseCase implements UseCaseContract
{
    /**
     * The resolved repository instance associated with this UseCase.
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
     * Initialize the UseCase instance with the RepositoryFactory dependency.
     *
     * @param  RepositoryFactory|null  $factory  The repository factory instance (optional).
     */
    public function __construct(?RepositoryFactory $factory = null)
    {
        // Store the factory instance or dynamically resolve from container / instantiate
        $this->factory = $factory ?? (function_exists('app') ? app(RepositoryFactory::class) : new RepositoryFactory());
    }

    /**
     * Dynamically resolve and bind the repository for this UseCase using its interface contract.
     *
     * @param  string  $interface  Fully qualified repository interface name (e.g. StatusRepositoryInterface::class).
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
     * Retrieve the resolved repository instance bound to this UseCase.
     *
     * @return mixed The resolved repository instance.
     */
    public function getRepository(): mixed
    {
        // Return the bound repository instance
        return $this->repository;
    }

    /**
     * Execute the atomic business logic of the use case for a single record.
     *
     * @param  ?DTO  $data  Input Data Transfer Object (or null for parameterless operations).
     * @return Outcome Structured outcome envelope containing result status and payload.
     */
    abstract public function handle(?DTO $data): Outcome;
}
