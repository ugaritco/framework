<?php

declare(strict_types=1);

namespace Heritage\Factories;

use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Class UseCaseFactory
 *
 * Factory class responsible for creating and resolving instances of UseCases dynamically.
 *
 * Architectural Role:
 * - Provides a centralized entry point to instantiate domain UseCases without coupling controllers.
 * - Leverages Ugarit's IoC service container to auto-wire dependencies declared in UseCase constructors
 *   (such as RepositoryFactory, validation services, notification handlers, etc.).
 */
class UseCaseFactory
{
    /**
     * The application service container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Initialize the UseCaseFactory with the application service container.
     *
     * @param  Container|null  $container  The application service container (optional).
     */
    public function __construct(?Container $container = null)
    {
        // Store the container instance or resolve from application context
        $this->container = $container ?? (function_exists('app') ? app() : \Heritage\Container\Container::getInstance());
    }

    /**
     * Resolve and instantiate a UseCase using the service container.
     *
     * Execution steps:
     * 1. Validate that the given UseCase class exists.
     * 2. Resolve the class through the container with automated dependency injection.
     *
     * @param  string  $useCaseClass  Fully qualified class name of the UseCase.
     * @return mixed Resolved UseCase instance ready for execution.
     *
     * @throws InvalidArgumentException If the specified class does not exist.
     * @throws BindingResolutionException If the container fails to resolve a dependency.
     */
    public function make(string $useCaseClass): mixed
    {
        // Step 1: Validate class existence prior to container resolution
        if (! class_exists($useCaseClass)) {
            // Throw explicit exception when the class is missing
            throw new InvalidArgumentException("UseCase class [{$useCaseClass}] not found.");
        }

        // Step 2: Resolve UseCase class through container with automatic dependency injection
        return $this->container->make($useCaseClass);
    }
}
