<?php

namespace Heritage\Routing;

use BadMethodCallException;
use Heritage\Capabilities\Capability;
use Heritage\Contracts\Capabilities\CapabilityContract;
use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Features\FeatureContract;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Parcels\Parcel;
use Heritage\Enums\Responders\ResponderType;
use Heritage\Factories\CapabilityFactory;
use Heritage\Factories\FeatureFactory;
use Heritage\Factories\ResponderFactory;
use Heritage\Features\Feature;
use Heritage\Http\Responders\Options\InertiaResponderOptions;
use Heritage\Http\Responders\Options\JsonResponderOptions;
use Heritage\Http\Responders\Options\RedirectBackResponderOptions;
use Heritage\Http\Responders\Options\RedirectToRouteResponderOptions;
use Heritage\Http\Responders\Responder;
use Heritage\Outcomes\Outcome;
use InvalidArgumentException;

abstract class Controller
{
    /**
     * The middleware registered on the controller.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * CapabilityFactory instance.
     */
    protected ?CapabilityFactory $capabilityFactory = null;



    /**
     * FeatureFactory instance.
     */
    protected ?FeatureFactory $featureFactory = null;

    /**
     * ResponderFactory instance.
     */
    protected ?ResponderFactory $responderFactory = null;

    /**
     * Register middleware on the controller.
     *
     * @param  \Closure|array|string  $middleware
     * @param  array  $options
     * @return \Heritage\Routing\ControllerMiddlewareOptions
     */
    public function middleware($middleware, array $options = [])
    {
        foreach ((array) $middleware as $m) {
            $this->middleware[] = [
                'middleware' => $m,
                'options' => &$options,
            ];
        }

        return new ControllerMiddlewareOptions($options);
    }

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        return $this->{$method}(...array_values($parameters));
    }

    /**
     * Retrieve the CapabilityFactory instance, lazily resolving it from the service container if uninitialized.
     *
     * @return CapabilityFactory
     */
    public function getCapabilityFactory(): CapabilityFactory
    {
        // Use null coalescing assignment to lazily resolve and cache the factory instance
        return $this->capabilityFactory ??= (function_exists('app') && app()->bound(CapabilityFactory::class)
            ? app(CapabilityFactory::class)
            : new CapabilityFactory());
    }

    /**
     * Explicitly set the CapabilityFactory instance (useful for unit testing and mocking).
     *
     * @param  CapabilityFactory  $capabilityFactory
     * @return static
     */
    public function setCapabilityFactory(CapabilityFactory $capabilityFactory): static
    {
        // Assign the factory instance to the protected property
        $this->capabilityFactory = $capabilityFactory;

        // Return current instance for fluent method chaining
        return $this;
    }



    /**
     * Retrieve the FeatureFactory instance, lazily resolving it from the service container if uninitialized.
     *
     * @return FeatureFactory
     */
    public function getFeatureFactory(): FeatureFactory
    {
        // Use null coalescing assignment to lazily resolve and cache the feature factory instance
        return $this->featureFactory ??= (function_exists('app') && app()->bound(FeatureFactory::class)
            ? app(FeatureFactory::class)
            : new FeatureFactory());
    }

    /**
     * Explicitly set the FeatureFactory instance (useful for unit testing and mocking).
     *
     * @param  FeatureFactory  $featureFactory
     * @return static
     */
    public function setFeatureFactory(FeatureFactory $featureFactory): static
    {
        // Assign the feature factory instance
        $this->featureFactory = $featureFactory;

        // Return current instance for fluent method chaining
        return $this;
    }

    /**
     * Retrieve the ResponderFactory instance, lazily resolving it from the service container if uninitialized.
     *
     * @return ResponderFactory
     */
    public function getResponderFactory(): ResponderFactory
    {
        // Use null coalescing assignment to lazily resolve and cache the responder factory instance
        return $this->responderFactory ??= (function_exists('app') && app()->bound(ResponderFactory::class)
            ? app(ResponderFactory::class)
            : new ResponderFactory());
    }

    /**
     * Explicitly set the ResponderFactory instance (useful for unit testing and mocking).
     *
     * @param  ResponderFactory  $responderFactory
     * @return static
     */
    public function setResponderFactory(ResponderFactory $responderFactory): static
    {
        // Assign the responder factory instance
        $this->responderFactory = $responderFactory;

        // Return current instance for fluent method chaining
        return $this;
    }

    /**
     * Resolve and instantiate an atomic Capability instance using the registered factory.
     *
     * @template T of CapabilityContract
     *
     * @param  class-string<T>  $capabilityClass  Fully qualified class name of the target Capability.
     * @return T Resolved Capability instance.
     *
     * @throws InvalidArgumentException If the resolved class is invalid or does not implement handle().
     */
    protected function capability(string $capabilityClass)
    {
        try {
            // Step 1: Use the CapabilityFactory to resolve the class from the service container
            $instance = $this->getCapabilityFactory()->make($capabilityClass);

            // Step 2: Validate that the resolved instance is an object
            if (! is_object($instance)) {
                throw new InvalidArgumentException('CapabilityFactory must return an object. Got: '.gettype($instance));
            }

            // Step 3: Ensure the instance implements the mandatory handle() method
            if (! method_exists($instance, 'handle')) {
                throw new InvalidArgumentException("Capability [{$capabilityClass}] does not implement handle().");
            }

            // Return the validated Capability instance
            return $instance;
        } catch (BindingResolutionException $e) {
            // Catch container resolution exceptions and rethrow a descriptive InvalidArgumentException
            throw new InvalidArgumentException("Unable to resolve Capability [{$capabilityClass}]: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve and instantiate a cross-artifact Feature instance using the registered factory.
     *
     * @template T of FeatureContract
     *
     * @param  class-string<T>  $featureClass  Fully qualified class name of the target Feature.
     * @return T Resolved Feature instance.
     *
     * @throws InvalidArgumentException If the resolved class is invalid or does not implement handle().
     */
    protected function feature(string $featureClass)
    {
        try {
            // Step 1: Use the FeatureFactory to resolve the class from the service container
            $instance = $this->getFeatureFactory()->make($featureClass);

            // Step 2: Validate that the resolved instance is an object
            if (! is_object($instance)) {
                throw new InvalidArgumentException('FeatureFactory must return an object. Got: '.gettype($instance));
            }

            // Step 3: Ensure the instance implements the mandatory handle() method
            if (! method_exists($instance, 'handle')) {
                throw new InvalidArgumentException("Feature [{$featureClass}] does not implement handle().");
            }

            // Return the validated Feature instance
            return $instance;
        } catch (BindingResolutionException $e) {
            // Catch container resolution exceptions and rethrow a descriptive InvalidArgumentException
            throw new InvalidArgumentException("Unable to resolve Feature [{$featureClass}]: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Helper to create a Responder instance based on the requested ResponderType.
     *
     * @param  ResponderType  $type  The responder type (defaults to REDIRECT_BACK).
     * @return Responder The specialized Responder instance.
     */
    protected function responder(ResponderType $type = ResponderType::REDIRECT_BACK): Responder
    {
        // Delegate instantiation to the ResponderFactory
        return $this->getResponderFactory()->make($type);
    }

    /**
     * Execute a Capability or Feature and retrieve the pure domain Outcome envelope.
     *
     * In the Ugarit architectural philosophy:
     * - Capability executes an atomic operation.
     * - Feature orchestrates a multi-step workflow.
     * - Outcome represents the pure domain execution result, agnostic to transport/consumer.
     *
     * @param string $actionClass Fully qualified class name of the Capability or Feature.
     * @param Parcel|ParcelContract|null $data Validated Parcel input.
     * @return Outcome Pure execution outcome envelope.
     */
    protected function run(
        string $actionClass,
        Parcel|ParcelContract|null $data = null
    ): Outcome {
        $executable = match (true) {
            is_subclass_of($actionClass, Capability::class) => $this->capability($actionClass),
            is_subclass_of($actionClass, Feature::class) => $this->feature($actionClass),
            default => app($actionClass),
        };

        return $executable->handle($data);
    }

    /**
     * Transform a domain Outcome envelope into the appropriate consumer Response using a Responder.
     *
     * In the Ugarit architectural philosophy:
     * - Responder receives the Outcome and converts it into the final Response presentation
     *   (Inertia view, JsonResponse, RedirectResponse, etc.) based on consumer requirements.
     * - Decouples business logic from presentation and transport protocols.
     *
     * @param Outcome $outcome The domain execution outcome.
     * @param ResponderType $responderType Desired presentation channel.
     * @param string|null $component Inertia component name (if applicable).
     * @param string|null $route Target route name for redirects (if applicable).
     * @param array|null $parameters Route parameters (if applicable).
     * @param array $extra Additional presentation metadata.
     * @return mixed Consumer Response (e.g., Inertia Response, JsonResponse, RedirectResponse).
     */
    protected function respond(
        Outcome $outcome,
        ResponderType $responderType = ResponderType::REDIRECT_BACK,
        ?string $component = null,
        ?string $route = null,
        ?array $parameters = null,
        array $extra = []
    ): mixed {
        $responder = $this->responder($responderType);

        $options = match ($responderType) {
            ResponderType::REDIRECT_BACK => new RedirectBackResponderOptions(
                outcome: $outcome,
                extra: $extra
            ),
            ResponderType::REDIRECT_TO_ROUTE => new RedirectToRouteResponderOptions(
                outcome: $outcome,
                routeName: $route ?? '',
                parameters: $parameters ?? [],
                extra: $extra
            ),
            ResponderType::INERTIA => new InertiaResponderOptions(
                outcome: $outcome,
                component: $component ?? 'Dashboard/Index',
                extra: array_merge(
                    [
                        'data' => is_array($outcome->data)
                            ? array_map(fn ($r) => is_object($r) && method_exists($r, 'resolve') ? $r->resolve() : $r, $outcome->data)
                            : (is_object($outcome->data) && method_exists($outcome->data, 'resolve') ? $outcome->data->resolve() : $outcome->data ?? []),
                    ],
                    $extra
                )
            ),
            ResponderType::JSON => new JsonResponderOptions(
                outcome: $outcome,
                extra: $extra
            ),
        };

        return $responder->respond($options);
    }

    /**
     * Execute a Capability or Feature workflow and transform its Outcome into a Response in a single call.
     *
     * Architectural Flow:
     * 1. Capability/Feature executes via run(), returning an Outcome.
     * 2. Responder transforms Outcome via respond(), returning the consumer Response.
     *
     * @param  string  $actionClass  Fully qualified class name of the Capability or Feature to execute.
     * @param  Parcel|ParcelContract|null  $data  Validated Parcel passed to handle().
     * @param  ResponderType  $responderType  Desired response channel (JSON, INERTIA, REDIRECT_BACK, REDIRECT_TO_ROUTE).
     * @param  string|null  $component  Inertia component name (required if responderType is INERTIA).
     * @param  string|null  $route  Target route name (used if responderType is REDIRECT_TO_ROUTE).
     * @param  array|null  $parameters  Route parameters for redirect.
     * @param  array  $extra  Additional metadata to merge into the response data.
     * @return mixed Final consumer Response object (JsonResponse, RedirectResponse, Inertia Response).
     */
    protected function execute(
        string $actionClass,
        Parcel|ParcelContract|null $data = null,
        ResponderType $responderType = ResponderType::REDIRECT_BACK,
        ?string $component = null,
        ?string $route = null,
        ?array $parameters = null,
        array $extra = []
    ) {
        $outcome = $this->run($actionClass, $data);

        return $this->respond(
            outcome: $outcome,
            responderType: $responderType,
            component: $component,
            route: $route,
            parameters: $parameters,
            extra: $extra
        );
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            static::class,
            $method
        ));
    }
}
