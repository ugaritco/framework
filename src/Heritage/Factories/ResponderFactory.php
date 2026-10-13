<?php

declare(strict_types=1);

namespace Heritage\Factories;

use Heritage\Container\Container;
use Heritage\Enums\Responders\ResponderType;
use Heritage\Http\Responders\InertiaResponder;
use Heritage\Http\Responders\JsonResponder;
use Heritage\Http\Responders\RedirectBackResponder;
use Heritage\Http\Responders\RedirectToRouteResponder;
use Heritage\Http\Responders\Responder;
use Heritage\Http\Responders\ViewResponder;
use InvalidArgumentException;

/**
 * Class ResponderFactory
 *
 * Creates the Responder of a ResponderType. Responders are resolved from the container, so an
 * application can bind its own class for a responder (or extend one) and inject dependencies.
 */
class ResponderFactory
{
    /**
     * The responder class of each concrete type.
     *
     * @var array<string, class-string<Responder>>
     */
    protected array $responders = [
        'json' => JsonResponder::class,
        'inertia' => InertiaResponder::class,
        'view' => ViewResponder::class,
        'redirect_back' => RedirectBackResponder::class,
        'redirect_to_route' => RedirectToRouteResponder::class,
    ];

    /**
     * @param  Container|null  $container  The container resolving responders (the global one by default).
     */
    public function __construct(protected ?Container $container = null)
    {
    }

    /**
     * Create the Responder of the given type.
     *
     * @param  ResponderType  $type  A concrete type; AUTO is resolved by the controller first.
     * @return Responder
     *
     * @throws InvalidArgumentException When the type has no responder (AUTO).
     */
    public function make(ResponderType $type): Responder
    {
        $class = $this->responders[$type->value]
            ?? throw new InvalidArgumentException("ResponderType::{$type->name} has no responder; resolve it to a concrete type first.");

        return ($this->container ?? Container::getInstance())->make($class);
    }

    /**
     * Use another Responder class for a type.
     *
     * @param  ResponderType  $type  The type to override.
     * @param  class-string<Responder>  $class  The responder class.
     * @return static
     */
    public function extend(ResponderType $type, string $class): static
    {
        $this->responders[$type->value] = $class;

        return $this;
    }
}
