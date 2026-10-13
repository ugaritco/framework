<?php

declare(strict_types=1);

namespace Heritage\Http\Responders;

use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Responders\Options\ResponderOptions;
use Heritage\Http\Responders\Options\ViewResponderOptions;
use Heritage\Http\Response;
use Heritage\Responses\Outcome;
use InvalidArgumentException;

/**
 * Renders an Outcome as a Blade view.
 *
 * The view receives:
 * - `$data`: the outcome's data; a Resource is resolved to its array, a model is passed as it is;
 * - `$outcome`: the Outcome (title(), message(), variant(), errors(), actions());
 * - the extra variables.
 *
 * The toast is placed in the session for the current request (`session('toast')`), as a
 * redirect flashes it for the next one, so one layout partial displays both.
 */
class ViewResponder extends Responder
{
    /**
     * Render the view of the given options (or an Outcome with the view name as first extra argument).
     *
     * @param  ResponderOptions|Outcome|null  $options  The view options, or the outcome.
     * @param  mixed  ...$extraArgs  With an Outcome: the view name, then the extra variables.
     * @return Response
     *
     * @throws InvalidArgumentException When no view is given or the view does not exist.
     */
    public function respond(ResponderOptions|Outcome|null $options = null, ...$extraArgs): Response
    {
        $options = $options ?? $this->options;

        if ($options instanceof Outcome) {
            $options = new ViewResponderOptions(
                outcome: $options,
                view: $extraArgs[0] ?? throw new InvalidArgumentException('A view response needs a view name.'),
                extra: $extraArgs[1] ?? []
            );
        }

        if (! $options instanceof ViewResponderOptions) {
            throw new InvalidArgumentException('Expected instance of ViewResponderOptions or Outcome');
        }

        if (! view()->exists($options->view)) {
            throw new InvalidArgumentException("View [{$options->view}] was not found.");
        }

        $outcome = $options->outcome;

        if ($toast = $outcome->toast()) {
            session()->now('toast', $toast->toArray());
        }

        return response()->view($options->view, [
            ...$options->extra,
            'data' => $outcome->data instanceof JsonResource ? $outcome->data->resolve() : $outcome->data,
            'outcome' => $outcome,
        ], $outcome->statusCode());
    }
}
