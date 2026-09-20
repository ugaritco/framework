<?php

declare(strict_types=1);

namespace Heritage\Factories;

use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Class RepositoryFactory
 *
 * Factory responsible for creating and resolving repository instances dynamically
 * to support Inversion of Control (IoC) and dependency decoupling.
 *
 * Resolution Strategy:
 * 1. Check if the interface is explicitly bound in the service container.
 * 2. Check if the given identifier is already an existing concrete class.
 * 3. Fallback to convention-based class name resolution:
 *    (e.g., StatusRepositoryInterface -> StatusRepository, stripping '\Contracts').
 */
class RepositoryFactory
{
    /**
     * The application IoC service container instance used to resolve dependencies.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Initialize the RepositoryFactory instance with the application container.
     *
     * @param  Container|null  $container  The application container instance (optional).
     */
    public function __construct(?Container $container = null)
    {
        // Fall back to singleton container instance if not explicitly provided
        $this->container = $container ?? (function_exists('app') ? app() : Container::getInstance());
    }

    /**
     * Resolve the concrete repository class name from the contract or interface name by convention.
     *
     * @param  string  $interface  Fully qualified interface or contract name.
     * @return string|null Resolved repository class name, or null if unresolvable.
     */
    public static function resolveClassName(string $interface): ?string
    {
        // Check if the given identifier is already an existing concrete class
        if (class_exists($interface)) {
            return $interface;
        }

        if (! interface_exists($interface)) {
            return null;
        }

        // Step 1: Strip 'Contract' or 'Interface' suffix (e.g. LocaleRepositoryContract -> LocaleRepository)
        $base = preg_replace('/(Contract|Interface)$/', '', $interface);

        // Step 2: Try mapping \Contracts\ to \Repositories\ (e.g. Artifacts\I18n\Contracts\... -> Artifacts\I18n\Repositories\...)
        if (str_contains($base, '\\Contracts\\')) {
            $candidate = str_replace('\\Contracts\\', '\\Repositories\\', $base);
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        // Step 3: Try removing \Contracts namespace segment (e.g. App\Contracts\Repositories -> App\Repositories)
        $candidate = str_replace('\\Contracts', '', $base);
        if (class_exists($candidate)) {
            return $candidate;
        }

        // Step 4: Check if the base name exists directly
        if (class_exists($base)) {
            return $base;
        }

        return null;
    }

    /**
     * Create and resolve a repository instance dynamically from an interface or class name.
     *
     * Resolution flow:
     * 1. Container check: If explicitly bound in the container, resolve directly.
     * 2. Concrete class check: If the passed identifier is a concrete class, resolve directly.
     * 3. Interface validation: Verify that the interface exists.
     * 4. Convention mapping: Strip 'Contract'/'Interface' and '\Contracts' segment to derive concrete repository.
     * 5. Resolve the derived class with automatic dependency injection via the container.
     *
     * @param  string  $interface  Fully qualified interface or class name.
     * @return mixed Resolved repository instance with all dependencies injected.
     *
     * @throws BindingResolutionException If container fails to resolve dependencies.
     * @throws InvalidArgumentException If interface or derived repository class cannot be found.
     */
    public function make(string $interface): mixed
    {
        // Step 1: Check if the interface is explicitly bound in the container
        if ($this->container->bound($interface)) {
            // Resolve directly from container with all configured bindings
            return $this->container->make($interface);
        }

        // Step 2: Check if the given string is already an existing concrete class
        if (class_exists($interface)) {
            // Resolve concrete class directly via container auto-wiring
            return $this->container->make($interface);
        }

        // Step 3: Validate that the interface exists before attempting convention derivation
        if (! interface_exists($interface)) {
            // Throw descriptive exception indicating missing interface
            throw new InvalidArgumentException("Interface or class [{$interface}] not found.");
        }

        // Step 4: Resolve repository class name by convention
        $repositoryClass = static::resolveClassName($interface);

        // Step 5: Check if the derived repository class exists
        if ($repositoryClass && class_exists($repositoryClass)) {
            // Resolve repository class through container, injecting Model and dependencies
            $instance = $this->container->make($repositoryClass);

            // Cache instance in container for subsequent singleton-like lookups
            if (! $this->container->bound($interface)) {
                $this->container->instance($interface, $instance);
            }

            return $instance;
        }

        // Throw exception when convention-based derivation cannot locate the target class
        throw new InvalidArgumentException("Repository class for interface [{$interface}] could not be resolved by convention.");
    }
}
