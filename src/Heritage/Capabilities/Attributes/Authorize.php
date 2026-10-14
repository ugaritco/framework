<?php

namespace Heritage\Capabilities\Attributes;

use Attribute;
use Heritage\Support\Facades\Gate;
use LogicException;
use ReflectionClass;
use ReflectionMethod;

/**
 * The ability a Capability or Feature requires, checked against the policies before it runs.
 *
 * The ability is checked against the given model when it is an instance of $model, otherwise
 * against $model itself (class-level abilities such as viewAny or create), and as a plain gate
 * when no model is given. An ability whose policy method requires a model fails loudly when none
 * is given. The controller checks it against the Parcel's model, or earlier, before validation,
 * a FormRequest declaring #[ForAction] checks it against the route model. Steps inside a
 * Feature's flow are not checked again.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Authorize
{
    /**
     * The request attribute recording the action whose ability was already granted.
     */
    public const GRANTED = 'heritage.authorized-action';

    /**
     * Create a new attribute instance.
     *
     * @param  string  $ability  The policy method or gate name.
     * @param  class-string|null  $model  The model the ability is about.
     */
    public function __construct(
        public string $ability,
        public ?string $model = null,
    ) {
        //
    }

    /**
     * The attribute declared on a Capability or Feature, if any.
     *
     * @param  object|class-string  $action
     * @return static|null
     */
    public static function of(object|string $action): ?static
    {
        return ((new ReflectionClass($action))->getAttributes(static::class)[0] ?? null)?->newInstance();
    }

    /**
     * Check the ability for the given action against the given model.
     *
     * @param  object|class-string  $action  The Capability or Feature, named in the error.
     * @param  object|null  $model  The model the action works on, if any.
     * @param  string  $hint  How to provide the model when the ability requires one.
     * @return void
     *
     * @throws \Heritage\Auth\Access\AuthorizationException When the ability is denied.
     * @throws LogicException When the ability requires a model and none is given.
     */
    public function check(object|string $action, ?object $model, string $hint): void
    {
        if ($this->model !== null
            && ! $model instanceof $this->model
            && $this->requiresInstance(Gate::getPolicyFor($this->model))) {
            throw new LogicException(sprintf(
                '[%s] checks [%s] on a [%s]: %s.',
                is_object($action) ? $action::class : $action, $this->ability, $this->model, $hint
            ));
        }

        Gate::authorize($this->ability, $this->arguments($model));
    }

    /**
     * The arguments given to the gate for the given model.
     *
     * @param  object|null  $attached  The model the action works on, if any.
     * @return array<int, object|string>
     */
    public function arguments(?object $attached): array
    {
        if ($this->model === null) {
            return [];
        }

        return [$attached instanceof $this->model ? $attached : $this->model];
    }

    /**
     * Whether the policy method of the ability requires a model instance (a required second parameter),
     * as view, update or delete usually do.
     *
     * @param  object|null  $policy  The policy of $model, if any.
     * @return bool
     */
    public function requiresInstance(?object $policy): bool
    {
        if ($policy === null || ! method_exists($policy, $this->ability)) {
            return false;
        }

        $parameter = (new ReflectionMethod($policy, $this->ability))->getParameters()[1] ?? null;

        return $parameter !== null && ! $parameter->isOptional() && ! $parameter->allowsNull();
    }
}
