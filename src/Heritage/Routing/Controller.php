<?php

namespace Heritage\Routing;

use BadMethodCallException;
use Heritage\Auth\Access\AuthorizationException;
use Heritage\Capabilities\Attributes\Authorize;
use Heritage\Contracts\Container\BindingResolutionException;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Contracts\Support\Arrayable;
use Heritage\Database\Eloquent\Model;
use Heritage\Enums\Responders\ResponderType;
use Heritage\Factories\ResponderFactory;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Resources\Json\ResourceCollection;
use Heritage\Http\Responders\Options\InertiaResponderOptions;
use Heritage\Http\Responders\Options\JsonResponderOptions;
use Heritage\Http\Responders\Options\RedirectBackResponderOptions;
use Heritage\Http\Responders\Options\RedirectToRouteResponderOptions;
use Heritage\Http\Responders\Options\ViewResponderOptions;
use Heritage\Http\Responders\Responder;
use Heritage\Outcomes\Outcome;
use Heritage\Pagination\AbstractCursorPaginator;
use Heritage\Pagination\AbstractPaginator;
use Heritage\Parcels\Parcel;
use Heritage\Support\Facades\Gate;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;

/**
 * Base controller of the Ugarit pipeline: runs a Capability or Feature, presents its data with
 * a Resource, and hands the Outcome to the Responder of the requested channel.
 *
 * - page() / back() / toRoute() / json() cover the usual responses; execute() and respond()
 *   take every option.
 * - ResponderType::AUTO answers JSON to clients expecting JSON, so one action serves pages and APIs.
 * - A failed outcome never renders its page nor redirects as if it succeeded: see respondToFailure().
 * - An action declaring #[Authorize] runs only once its ability is granted: see authorizeAction().
 */
abstract class Controller
{
    /**
     * The middleware registered on the controller.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * ResponderFactory instance.
     */
    protected ?ResponderFactory $responderFactory = null;

    /**
     * How page() renders this controller's pages (INERTIA or VIEW); null follows `view.pages`.
     */
    protected ?ResponderType $pages = null;

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
     * Retrieve the ResponderFactory, resolving it from the container on first use.
     *
     * @return ResponderFactory
     */
    public function getResponderFactory(): ResponderFactory
    {
        return $this->responderFactory ??= app(ResponderFactory::class);
    }

    /**
     * Explicitly set the ResponderFactory instance (useful for unit testing and mocking).
     *
     * @param  ResponderFactory  $responderFactory
     * @return static
     */
    public function setResponderFactory(ResponderFactory $responderFactory): static
    {
        $this->responderFactory = $responderFactory;

        return $this;
    }

    /**
     * Run an action and render its outcome as a page (JSON for clients expecting JSON).
     *
     * The page is an Inertia component or a Blade view, as pageResponderType() decides.
     *
     * @param  string  $actionClass  The Capability or Feature.
     * @param  Parcel|ParcelContract|null  $data  The validated input.
     * @param  string  $component  The Inertia component ("company::branch/Show") or Blade view ("company::branch.show").
     * @param  class-string<JsonResource>|null  $resource  Resource presenting the outcome's data.
     * @param  array  $extra  Additional props.
     * @return mixed
     */
    protected function page(
        string $actionClass,
        Parcel|ParcelContract|null $data,
        string $component,
        ?string $resource = null,
        array $extra = []
    ): mixed {
        return $this->execute($actionClass, $data, ResponderType::AUTO, $component, extra: $extra, resource: $resource);
    }

    /**
     * Run an action and redirect back with its feedback (JSON for clients expecting JSON).
     *
     * @param  string  $actionClass  The Capability or Feature.
     * @param  Parcel|ParcelContract|null  $data  The validated input.
     * @param  class-string<JsonResource>|null  $resource  Resource presenting the data of a JSON response.
     * @return mixed
     */
    protected function back(string $actionClass, Parcel|ParcelContract|null $data = null, ?string $resource = null): mixed
    {
        return $this->execute($actionClass, $data, ResponderType::AUTO, resource: $resource);
    }

    /**
     * Run an action and redirect to a named route once it succeeds (JSON for clients expecting JSON).
     *
     * @param  string  $actionClass  The Capability or Feature.
     * @param  Parcel|ParcelContract|null  $data  The validated input.
     * @param  string  $route  The route name.
     * @param  array  $parameters  The route parameters.
     * @return mixed
     */
    protected function toRoute(
        string $actionClass,
        Parcel|ParcelContract|null $data,
        string $route,
        array $parameters = []
    ): mixed {
        return $this->execute($actionClass, $data, ResponderType::AUTO, route: $route, parameters: $parameters);
    }

    /**
     * Run an action and answer JSON.
     *
     * @param  string  $actionClass  The Capability or Feature.
     * @param  Parcel|ParcelContract|null  $data  The validated input.
     * @param  class-string<JsonResource>|null  $resource  Resource presenting the outcome's data.
     * @param  array  $extra  Additional response keys.
     * @return mixed
     */
    protected function json(
        string $actionClass,
        Parcel|ParcelContract|null $data = null,
        ?string $resource = null,
        array $extra = []
    ): mixed {
        return $this->execute($actionClass, $data, ResponderType::JSON, extra: $extra, resource: $resource);
    }

    /**
     * Resolve a Capability or Feature from the container, injecting its constructor dependencies.
     *
     * @template T of object
     *
     * @param  class-string<T>  $actionClass  Fully qualified class name of the Capability or Feature.
     * @return T Resolved instance.
     *
     * @throws InvalidArgumentException If the class does not exist, cannot be resolved, or has no handle().
     */
    protected function action(string $actionClass): object
    {
        if (! class_exists($actionClass)) {
            throw new InvalidArgumentException("Action class [{$actionClass}] not found.");
        }

        try {
            $instance = app()->make($actionClass);
        } catch (BindingResolutionException $e) {
            throw new InvalidArgumentException("Unable to resolve [{$actionClass}]: ".$e->getMessage(), 0, $e);
        }

        if (! method_exists($instance, 'handle')) {
            throw new InvalidArgumentException("[{$actionClass}] does not implement handle().");
        }

        return $instance;
    }

    /**
     * Create the Responder of a concrete ResponderType.
     *
     * @param  ResponderType  $type  The responder type (defaults to REDIRECT_BACK).
     * @return Responder
     */
    protected function responder(ResponderType $type = ResponderType::REDIRECT_BACK): Responder
    {
        return $this->getResponderFactory()->make($type);
    }

    /**
     * Run a Capability or Feature and return its Outcome, once the ability it declares is granted.
     *
     * @param  string  $actionClass  Fully qualified class name of the Capability or Feature.
     * @param  Parcel|ParcelContract|null  $data  Validated Parcel input.
     * @return Outcome
     *
     * @throws AuthorizationException When the declared ability is denied.
     */
    protected function run(
        string $actionClass,
        Parcel|ParcelContract|null $data = null
    ): Outcome {
        $action = $this->action($actionClass);

        $this->authorizeAction($action, $data);

        return $action->handle($data);
    }

    /**
     * Check the ability the action declares with #[Authorize], if any.
     *
     * @param  object  $action  The Capability or Feature.
     * @param  Parcel|ParcelContract|null  $data  The Parcel it will receive.
     * @return void
     *
     * @throws AuthorizationException When the ability is denied.
     * @throws LogicException When the ability requires a model the Parcel does not carry.
     */
    protected function authorizeAction(object $action, Parcel|ParcelContract|null $data): void
    {
        $attribute = (new ReflectionClass($action))->getAttributes(Authorize::class)[0] ?? null;

        if ($attribute === null) {
            return;
        }

        $authorize = $attribute->newInstance();
        $attached = $data?->getModel();

        if ($authorize->model !== null
            && ! $attached instanceof $authorize->model
            && $authorize->requiresInstance(Gate::getPolicyFor($authorize->model))) {
            throw new LogicException(sprintf(
                '[%s] checks [%s] on a [%s]: attach the model to its parcel (setModel() or fromModel()).',
                $action::class, $authorize->ability, $authorize->model
            ));
        }

        Gate::authorize($authorize->ability, $authorize->arguments($attached));
    }

    /**
     * Turn an Outcome into the response of the requested channel.
     *
     * AUTO is resolved per request (see resolveResponderType()). A failed outcome bound for a page
     * or a route redirect is handled by respondToFailure() instead.
     *
     * @param  Outcome  $outcome  The domain outcome.
     * @param  ResponderType  $responderType  The presentation channel.
     * @param  string|null  $component  Inertia component or Blade view (required for INERTIA / VIEW).
     * @param  string|null  $route  Target route name (required for REDIRECT_TO_ROUTE).
     * @param  array|null  $parameters  Route parameters.
     * @param  array  $extra  Additional presentation metadata.
     * @param  class-string<JsonResource>|null  $resource  Resource presenting the outcome's data (see present()).
     * @return mixed The Inertia response, JsonResponse or RedirectResponse.
     *
     * @throws InvalidArgumentException When the component or route the channel needs is missing.
     */
    protected function respond(
        Outcome $outcome,
        ResponderType $responderType = ResponderType::REDIRECT_BACK,
        ?string $component = null,
        ?string $route = null,
        ?array $parameters = null,
        array $extra = [],
        ?string $resource = null
    ): mixed {
        $type = $this->resolveResponderType($responderType, $component, $route);

        if ($type === ResponderType::INERTIA && ($component === null || $component === '')) {
            throw new InvalidArgumentException('An Inertia response needs a component.');
        }

        if ($type === ResponderType::VIEW && ($component === null || $component === '')) {
            throw new InvalidArgumentException('A view response needs a view name.');
        }

        if ($type === ResponderType::REDIRECT_TO_ROUTE && ($route === null || $route === '')) {
            throw new InvalidArgumentException('A redirect to a route needs the route name.');
        }

        if ($outcome->isFailure() && in_array($type, [ResponderType::INERTIA, ResponderType::VIEW, ResponderType::REDIRECT_TO_ROUTE], true)) {
            return $this->respondToFailure($outcome);
        }

        $outcome = $this->present($outcome, $resource);

        $options = match ($type) {
            ResponderType::REDIRECT_BACK => new RedirectBackResponderOptions(
                outcome: $outcome,
                extra: $extra
            ),
            ResponderType::REDIRECT_TO_ROUTE => new RedirectToRouteResponderOptions(
                outcome: $outcome,
                routeName: $route,
                parameters: $parameters ?? [],
                extra: $extra
            ),
            ResponderType::INERTIA => new InertiaResponderOptions(
                outcome: $outcome,
                component: $component,
                extra: array_merge(['data' => $this->presentationData($outcome->data)], $extra)
            ),
            ResponderType::VIEW => new ViewResponderOptions(
                outcome: $outcome,
                view: $component,
                extra: $extra
            ),
            ResponderType::JSON => new JsonResponderOptions(
                outcome: $outcome,
                extra: $extra
            ),
        };

        return $this->responder($type)->respond($options);
    }

    /**
     * Run a Capability or Feature and turn its Outcome into a response in one call.
     *
     * @param  string  $actionClass  Fully qualified class name of the Capability or Feature.
     * @param  Parcel|ParcelContract|null  $data  Validated Parcel passed to handle().
     * @param  ResponderType  $responderType  The presentation channel.
     * @param  string|null  $component  Inertia component (required for INERTIA).
     * @param  string|null  $route  Target route name (required for REDIRECT_TO_ROUTE).
     * @param  array|null  $parameters  Route parameters for redirect.
     * @param  array  $extra  Additional metadata to merge into the response data.
     * @param  class-string<JsonResource>|null  $resource  Resource presenting the outcome's data.
     * @return mixed The Inertia response, JsonResponse or RedirectResponse.
     */
    protected function execute(
        string $actionClass,
        Parcel|ParcelContract|null $data = null,
        ResponderType $responderType = ResponderType::REDIRECT_BACK,
        ?string $component = null,
        ?string $route = null,
        ?array $parameters = null,
        array $extra = [],
        ?string $resource = null
    ) {
        return $this->respond(
            outcome: $this->run($actionClass, $data),
            responderType: $responderType,
            component: $component,
            route: $route,
            parameters: $parameters,
            extra: $extra,
            resource: $resource
        );
    }

    /**
     * The channel rendering pages: the controller's $pages, otherwise the `view.pages` config
     * ("inertia" or "blade").
     *
     * @return ResponderType INERTIA or VIEW.
     *
     * @throws InvalidArgumentException When the configured value is unknown.
     */
    protected function pageResponderType(): ResponderType
    {
        if ($this->pages !== null) {
            return $this->pages;
        }

        $pages = config('view.pages', 'inertia');

        return match ($pages) {
            'inertia' => ResponderType::INERTIA,
            'blade', 'view' => ResponderType::VIEW,
            default => throw new InvalidArgumentException("Unknown view.pages [{$pages}]; use \"inertia\" or \"blade\"."),
        };
    }

    /**
     * Resolve AUTO to a concrete channel: JSON when the client expects JSON and is not an Inertia
     * visit; otherwise the page (Inertia or Blade), the route redirect or a redirect back, by what is given.
     *
     * @param  ResponderType  $type  The requested channel.
     * @param  string|null  $component  The Inertia component, if any.
     * @param  string|null  $route  The route name, if any.
     * @return ResponderType A concrete channel.
     */
    protected function resolveResponderType(ResponderType $type, ?string $component, ?string $route): ResponderType
    {
        if ($type !== ResponderType::AUTO) {
            return $type;
        }

        $request = request();

        if (! $request->header('X-Inertia') && $request->expectsJson()) {
            return ResponderType::JSON;
        }

        return match (true) {
            $component !== null => $this->pageResponderType(),
            $route !== null => ResponderType::REDIRECT_TO_ROUTE,
            default => ResponderType::REDIRECT_BACK,
        };
    }

    /**
     * Respond to a failed outcome that was bound for a page or a route redirect.
     *
     * A 404 outcome aborts with Not Found; any other failure redirects back with the outcome's
     * feedback and errors. Override to change it for a controller.
     *
     * @param  Outcome  $outcome  The failed outcome.
     * @return mixed
     */
    protected function respondToFailure(Outcome $outcome): mixed
    {
        if ($outcome->statusCode() === 404) {
            abort(404, $outcome->message());
        }

        return $this->responder(ResponderType::REDIRECT_BACK)
            ->respond(new RedirectBackResponderOptions(outcome: $outcome));
    }

    /**
     * Present the outcome's domain data with the given Resource.
     *
     * A model (or any single value) is wrapped with `make()`; a collection, paginator or array
     * with `collection()`. Data that is null or already a Resource is left as it is.
     *
     * @param  Outcome  $outcome  The domain outcome.
     * @param  class-string<JsonResource>|null  $resource  The Resource class, or null to keep the data.
     * @return Outcome The outcome carrying the presented data.
     *
     * @throws InvalidArgumentException When the class is not a JsonResource.
     */
    protected function present(Outcome $outcome, ?string $resource): Outcome
    {
        $data = $outcome->data;

        if ($resource === null || $data === null || $data instanceof JsonResource) {
            return $outcome;
        }

        if (! is_a($resource, JsonResource::class, true)) {
            throw new InvalidArgumentException("[{$resource}] is not a JsonResource.");
        }

        return $outcome->withData(
            ! $data instanceof Model && is_iterable($data) ? $resource::collection($data) : $resource::make($data)
        );
    }

    /**
     * Resolve the outcome's data into the array given to an Inertia page.
     *
     * @param  mixed  $data  The (presented) outcome data.
     * @return mixed
     */
    protected function presentationData(mixed $data): mixed
    {
        return match (true) {
            $data === null => [],
            $data instanceof ResourceCollection && ($data->resource instanceof AbstractPaginator || $data->resource instanceof AbstractCursorPaginator) => array_merge(
                $data->resource->toArray(),
                ['data' => $data->resolve()]
            ),
            $data instanceof JsonResource => $data->resolve(),
            $data instanceof Arrayable => $data->toArray(),
            is_array($data) => array_map(fn ($value) => $this->presentationData($value), $data),
            default => $data,
        };
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
