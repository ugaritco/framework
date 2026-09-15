<?php

namespace Heritage\Auth\Middleware;

use Closure;
use Heritage\Contracts\Routing\ResponseFactory;
use Heritage\Contracts\Routing\UrlGenerator;
use Heritage\Support\Facades\Date;

class RequirePassword
{
    /**
     * The response factory instance.
     *
     * @var \Heritage\Contracts\Routing\ResponseFactory
     */
    protected $responseFactory;

    /**
     * The URL generator instance.
     *
     * @var \Heritage\Contracts\Routing\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * The password timeout.
     *
     * @var int
     */
    protected $passwordTimeout;

    /**
     * Create a new middleware instance.
     *
     * @param  \Heritage\Contracts\Routing\ResponseFactory  $responseFactory
     * @param  \Heritage\Contracts\Routing\UrlGenerator  $urlGenerator
     * @param  int|null  $passwordTimeout
     */
    public function __construct(ResponseFactory $responseFactory, UrlGenerator $urlGenerator, $passwordTimeout = null)
    {
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
        $this->passwordTimeout = $passwordTimeout ?: 10800;
    }

    /**
     * Specify the redirect route and timeout for the middleware.
     *
     * @param  string|null  $redirectToRoute
     * @param  string|int|null  $passwordTimeoutSeconds
     * @return string
     *
     * @named-arguments-supported
     */
    public static function using($redirectToRoute = null, $passwordTimeoutSeconds = null)
    {
        return static::class.':'.implode(',', func_get_args());
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @param  string|int|null  $passwordTimeoutSeconds
     * @return mixed
     */
    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null)
    {
        if ($this->shouldConfirmPassword($request, $passwordTimeoutSeconds)) {
            if ($request->expectsJson()) {
                return $this->responseFactory->json([
                    'message' => 'Password confirmation required.',
                ], 423);
            }

            return $this->responseFactory->redirectGuest(
                $this->urlGenerator->route($redirectToRoute ?: 'password.confirm')
            );
        }

        return $next($request);
    }

    /**
     * Determine if the confirmation timeout has expired.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  int|null  $passwordTimeoutSeconds
     * @return bool
     */
    protected function shouldConfirmPassword($request, $passwordTimeoutSeconds = null)
    {
        $confirmedAt = Date::now()->unix() - $request->session()->get('auth.password_confirmed_at', 0);

        return $confirmedAt > ($passwordTimeoutSeconds ?? $this->passwordTimeout);
    }
}
