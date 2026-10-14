<?php

namespace Heritage\Capabilities\Attributes;

use Attribute;
use ReflectionMethod;

/**
 * The ability a Capability or Feature requires, checked against the policies before it runs.
 *
 * The ability is checked against the Parcel's attached model when it is an instance of $model,
 * otherwise against $model itself (class-level abilities such as viewAny or create), and as a
 * plain gate when no model is given. An ability whose policy method requires a model fails loudly
 * when the Parcel carries none. Steps inside a Feature's flow are not checked again.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Authorize
{
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
     * The arguments given to the gate for the given Parcel model.
     *
     * @param  object|null  $attached  The model attached to the Parcel, if any.
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
