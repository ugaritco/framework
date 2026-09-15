<?php

namespace Heritage\Routing\Middleware;

use Closure;
use Heritage\Contracts\Routing\Registrar;
use Heritage\Database\Eloquent\ModelNotFoundException;

class SubstituteBindings
{
    /**
     * The router instance.
     *
     * @var \Heritage\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * Create a new bindings substitutor.
     *
     * @param  \Heritage\Contracts\Routing\Registrar  $router
     */
    public function __construct(Registrar $router)
    {
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Heritage\Database\Eloquent\ModelNotFoundException
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();

        try {
            $this->router->substituteBindings($route);
            $this->router->substituteImplicitBindings($route);
        } catch (ModelNotFoundException $exception) {
            if ($route->getMissing()) {
                return $route->getMissing()($request, $exception);
            }

            throw $exception;
        }

        return $next($request);
    }
}
