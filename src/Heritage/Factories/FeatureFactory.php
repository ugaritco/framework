<?php

declare(strict_types=1);

namespace Heritage\Factories;

use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Class FeatureFactory
 *
 * Factory responsible for resolving and instantiating cross-module Features dynamically
 * through Ugarit's service container with automatic dependency injection.
 */
class FeatureFactory
{
    /**
     * The application service container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Initialize the FeatureFactory with the application container.
     *
     * @param  Container|null  $container  The application container (optional).
     */
    public function __construct(?Container $container = null)
    {
        // Store the container instance or resolve from application context
        $this->container = $container ?? (function_exists('app') ? app() : \Heritage\Container\Container::getInstance());
    }

    /**
     * Resolve and instantiate a Feature using the service container.
     *
     * @param  string  $featureClass  Fully qualified class name of the Feature.
     * @return mixed Resolved Feature instance.
     *
     * @throws InvalidArgumentException If the specified class does not exist.
     * @throws BindingResolutionException If the container fails to resolve a dependency.
     */
    public function make(string $featureClass): mixed
    {
        // Validate class existence prior to resolution
        if (! class_exists($featureClass)) {
            throw new InvalidArgumentException("Feature class [{$featureClass}] not found.");
        }

        // Resolve Feature class through container with automatic dependency injection
        return $this->container->make($featureClass);
    }
}
