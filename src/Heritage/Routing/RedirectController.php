<?php

namespace Heritage\Routing;

use Heritage\Http\RedirectResponse;
use Heritage\Http\Request;
use Heritage\Support\Collection;
use Heritage\Support\Str;

class RedirectController extends Controller
{
    /**
     * Invoke the controller method.
     *
     * @param  \Heritage\Http\Request  $request
     * @param  \Heritage\Routing\UrlGenerator  $url
     * @return \Heritage\Http\RedirectResponse
     */
    public function __invoke(Request $request, UrlGenerator $url)
    {
        $parameters = new Collection($request->route()->parameters());

        $status = $parameters->get('status');

        $destination = $parameters->get('destination');

        $parameters->forget('status')->forget('destination');

        $route = (new Route('GET', $destination, [
            'as' => 'ugarit_route_redirect_destination',
        ]))->bind($request);

        $parameters = $parameters->only(
            $route->getCompiled()->getPathVariables()
        )->all();

        $url = $url->toRoute($route, $parameters, false);

        if (! str_starts_with($destination, '/') && str_starts_with($url, '/')) {
            $url = Str::after($url, '/');
        }

        return new RedirectResponse($url, $status);
    }
}
