<?php

namespace Heritage\Routing;

use BadMethodCallException;
use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Features\FeatureContract;
use Heritage\Contracts\Services\ServiceContract;
use Heritage\Contracts\UseCases\UseCaseContract;
use Heritage\DTOs\DTO;
use Heritage\Enums\Responders\ResponderType;
use Heritage\Factories\FeatureFactory;
use Heritage\Factories\ResponderFactory;
use Heritage\Factories\ServiceFactory;
use Heritage\Factories\UseCaseFactory;
use Heritage\Features\Feature;
use Heritage\Http\Responders\Options\InertiaResponderOptions;
use Heritage\Http\Responders\Options\JsonResponderOptions;
use Heritage\Http\Responders\Options\RedirectBackResponderOptions;
use Heritage\Http\Responders\Options\RedirectToRouteResponderOptions;
use Heritage\Http\Responders\Responder;
use Heritage\Responses\Outcome;
use Heritage\Services\Service;
use Heritage\Services\UseCase;
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
     * UseCaseFactory instance.
     */
    protected ?UseCaseFactory $useCaseFactory = null;

    /**
     * ServiceFactory instance.
     */
    protected ?ServiceFactory $serviceFactory = null;

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
     * Retrieve the UseCaseFactory instance, lazily resolving it from the service container if uninitialized.
     *
     * @return UseCaseFactory
     */
    public function getUseCaseFactory(): UseCaseFactory
    {
        // Use null coalescing assignment to lazily resolve and cache the factory instance
        return $this->useCaseFactory ??= (function_exists('app') && app()->bound(UseCaseFactory::class)
            ? app(UseCaseFactory::class)
            : new UseCaseFactory());
    }

    /**
     * Explicitly set the UseCaseFactory instance (useful for unit testing and mocking).
     *
     * @param  UseCaseFactory  $useCaseFactory
     * @return static
     */
    public function setUseCaseFactory(UseCaseFactory $useCaseFactory): static
    {
        // Assign the factory instance to the protected property
        $this->useCaseFactory = $useCaseFactory;

        // Return current instance for fluent method chaining
        return $this;
    }

    /**
     * Retrieve the ServiceFactory instance, lazily resolving it from the service container if uninitialized.
     *
     * @return ServiceFactory
     */
    public function getServiceFactory(): ServiceFactory
    {
        // Use null coalescing assignment to lazily resolve and cache the service factory instance
        return $this->serviceFactory ??= (function_exists('app') && app()->bound(ServiceFactory::class)
            ? app(ServiceFactory::class)
            : new ServiceFactory());
    }

    /**
     * Explicitly set the ServiceFactory instance (useful for unit testing and mocking).
     *
     * @param  ServiceFactory  $serviceFactory
     * @return static
     */
    public function setServiceFactory(ServiceFactory $serviceFactory): static
    {
        // Assign the service factory instance
        $this->serviceFactory = $serviceFactory;

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
     * Resolve and instantiate an atomic UseCase instance using the registered factory.
     *
     * @template T of UseCaseContract
     *
     * @param  class-string<T>  $useCaseClass  Fully qualified class name of the target UseCase.
     * @return T Resolved UseCase instance.
     *
     * @throws InvalidArgumentException If the resolved class is invalid or does not implement handle().
     */
    protected function useCase(string $useCaseClass)
    {
        try {
            // Step 1: Use the UseCaseFactory to resolve the class from the service container
            $instance = $this->getUseCaseFactory()->make($useCaseClass);

            // Step 2: Validate that the resolved instance is an object
            if (! is_object($instance)) {
                throw new InvalidArgumentException('UseCaseFactory must return an object. Got: '.gettype($instance));
            }

            // Step 3: Ensure the instance implements the mandatory handle() method
            if (! method_exists($instance, 'handle')) {
                throw new InvalidArgumentException("UseCase [{$useCaseClass}] does not implement handle().");
            }

            // Return the validated UseCase instance
            return $instance;
        } catch (BindingResolutionException $e) {
            // Catch container resolution exceptions and rethrow a descriptive InvalidArgumentException
            throw new InvalidArgumentException("Unable to resolve UseCase [{$useCaseClass}]: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve and instantiate an intra-module Service instance using the registered factory.
     *
     * @template T of ServiceContract
     *
     * @param  class-string<T>  $serviceClass  Fully qualified class name of the target Service.
     * @return T Resolved Service instance.
     *
     * @throws InvalidArgumentException If the resolved class is invalid or does not implement handle().
     */
    protected function service(string $serviceClass)
    {
        try {
            // Step 1: Use the ServiceFactory to resolve the class from the service container
            $instance = $this->getServiceFactory()->make($serviceClass);

            // Step 2: Validate that the resolved instance is an object
            if (! is_object($instance)) {
                throw new InvalidArgumentException('ServiceFactory must return an object. Got: '.gettype($instance));
            }

            // Step 3: Ensure the instance implements the mandatory handle() method
            if (! method_exists($instance, 'handle')) {
                throw new InvalidArgumentException("Service [{$serviceClass}] does not implement handle().");
            }

            // Return the validated Service instance
            return $instance;
        } catch (BindingResolutionException $e) {
            // Catch container resolution exceptions and rethrow a descriptive InvalidArgumentException
            throw new InvalidArgumentException("Unable to resolve Service [{$serviceClass}]: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve and instantiate a cross-module Feature instance using the registered factory.
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
     * Execute a UseCase, Service, or Feature workflow and generate an HTTP response using a specified Responder type.
     *
     * Architectural Flow:
     * 1. Detect and resolve the executable instance (UseCase, Service, or Feature) via its dedicated factory.
     * 2. Execute business logic by passing the validated DTO to handle(), obtaining an Outcome envelope.
     * 3. Instantiate the specialized Responder (Json, Inertia, Redirect) via ResponderFactory.
     * 4. Wrap the Outcome into the corresponding strongly-typed ResponderOptions DTO.
     * 5. Call respond() on the Responder to generate and return the final HTTP response.
     *
     * @param  string  $actionClass  Fully qualified class name of the UseCase, Service, or Feature to execute.
     * @param  ?DTO  $data  Validated Data Transfer Object passed to handle().
     * @param  ResponderType  $responderType  Desired response channel (JSON, INERTIA, REDIRECT_BACK, REDIRECT_TO_ROUTE).
     * @param  string|null  $component  Inertia component name (required if responderType is INERTIA).
     * @param  string|null  $route  Target route name (used if responderType is REDIRECT_TO_ROUTE).
     * @param  array|null  $parameters  Route parameters for redirect.
     * @param  array  $extra  Additional metadata to merge into the response data.
     * @return mixed Final HTTP response object (JsonResponse, RedirectResponse, Inertia Response).
     */
    protected function execute(
        string $actionClass,
        ?DTO $data = null,
        ResponderType $responderType = ResponderType::REDIRECT_BACK,
        ?string $component = null,
        ?string $route = null,
        ?array $parameters = null,
        array $extra = []
    ) {
        // Step 1: Detect executable type and resolve via the appropriate factory
        $executable = match (true) {
            is_subclass_of($actionClass, UseCase::class) => $this->useCase($actionClass),
            is_subclass_of($actionClass, Service::class) => $this->service($actionClass),
            is_subclass_of($actionClass, Feature::class) => $this->feature($actionClass),
            default => app($actionClass),
        };

        // Step 2: Execute domain logic and retrieve the Outcome envelope
        /** @var Outcome $outcome */
        $outcome = $executable->handle($data);

        // Step 3: Instantiate the specialized Responder matching the requested ResponderType
        $responder = $this->responder($responderType);

        // Step 4: Wrap the Outcome into the appropriate Options DTO for the Responder
        $options = match ($responderType) {
            // Options for redirecting back to the previous URL
            ResponderType::REDIRECT_BACK => new RedirectBackResponderOptions(
                outcome: $outcome,
                extra: $extra
            ),

            // Options for redirecting to a specific named route with parameters
            ResponderType::REDIRECT_TO_ROUTE => new RedirectToRouteResponderOptions(
                outcome: $outcome,
                routeName: $route ?? '',
                parameters: $parameters ?? [],
                extra: $extra
            ),

            // Options for rendering Inertia.js components with resolved resource data
            ResponderType::INERTIA => new InertiaResponderOptions(
                outcome: $outcome,
                component: $component ?? 'Dashboard/Index',
                extra: array_merge(
                    [
                        // Resolve JsonResources or ResourceCollections to clean arrays
                        'data' => is_array($outcome->data)
                            ? array_map(fn ($r) => method_exists($r, 'resolve') ? $r->resolve() : $r, $outcome->data)
                            : (method_exists($outcome->data, 'resolve') ? $outcome->data->resolve() : $outcome->data ?? []),
                    ],
                    $extra
                )
            ),

            // Options for returning structured JSON responses
            ResponderType::JSON => new JsonResponderOptions(
                outcome: $outcome,
                extra: $extra
            ),
        };

        // Step 5: Convert the Options DTO into the final HTTP response
        return $responder->respond($options);
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
