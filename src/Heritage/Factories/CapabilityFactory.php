<?php

declare(strict_types=1);

namespace Heritage\Factories;

use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Class CapabilityFactory
 *
 * Factory responsible for resolving and instantiating domain Capabilities dynamically
 * through Ugarit's service container with automatic dependency injection.
 */
class CapabilityFactory
{
    /**
     * The application service container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Initialize the CapabilityFactory with the application container.
     *
     * @param  Container|null  $container  The application container (optional).
     */
    public function __construct(?Container $container = null)
    {
        // Store the container instance or resolve from application context
        $this->container = $container ?? (function_exists('app') ? app() : \Heritage\Container\Container::getInstance());
    }

    /**
     * Resolve and instantiate a Capability using the service container.
     *
     * @param  string  $capabilityClass  Fully qualified class name of the Capability.
     * @return mixed Resolved Capability instance.
     *
     * @throws InvalidArgumentException If the specified class does not exist.
     * @throws BindingResolutionException If the container fails to resolve a dependency.
     */
    public function make(string $capabilityClass): mixed
    {
        // Validate class existence prior to resolution
        if (! class_exists($capabilityClass)) {
            throw new InvalidArgumentException("Capability class [{$capabilityClass}] not found.");
        }

        // Resolve Capability class through container with automatic dependency injection
        return $this->container->make($capabilityClass);
    }
}
