<?php

declare(strict_types=1);

namespace Heritage\Factories;

use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Class ServiceFactory
 *
 * Factory responsible for resolving and instantiating intra-module Services dynamically
 * through Ugarit's service container with automatic dependency injection.
 */
class ServiceFactory
{
    /**
     * The application service container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Initialize the ServiceFactory with the application container.
     *
     * @param  Container|null  $container  The application container (optional).
     */
    public function __construct(?Container $container = null)
    {
        // Store the container instance or resolve from application context
        $this->container = $container ?? (function_exists('app') ? app() : \Heritage\Container\Container::getInstance());
    }

    /**
     * Resolve and instantiate a Service using the service container.
     *
     * @param  string  $serviceClass  Fully qualified class name of the Service.
     * @return mixed Resolved Service instance.
     *
     * @throws InvalidArgumentException If the specified class does not exist.
     * @throws BindingResolutionException If the container fails to resolve a dependency.
     */
    public function make(string $serviceClass): mixed
    {
        // Validate class existence prior to resolution
        if (! class_exists($serviceClass)) {
            throw new InvalidArgumentException("Service class [{$serviceClass}] not found.");
        }

        // Resolve Service class through container with automatic dependency injection
        return $this->container->make($serviceClass);
    }
}
