<?php

namespace Heritage\Routing;

use Heritage\Support\Reflector;
use Heritage\Support\Str;
use ReflectionFunction;
use ReflectionMethod;

class RouteSignatureParameters
{
    /**
     * Extract the route action's signature parameters.
     *
     * @param  array  $action
     * @param  array  $conditions
     * @return array
     */
    public static function fromAction(array $action, $conditions = [])
    {
        $callback = RouteAction::containsSerializedClosure($action)
            ? unserialize($action['uses'], ['allowed_classes' => [
                \Ugarit\SerializableClosure\SerializableClosure::class,
                \Ugarit\SerializableClosure\UnsignedSerializableClosure::class,
                \Ugarit\SerializableClosure\Serializers\Native::class,
                \Ugarit\SerializableClosure\Serializers\Signed::class,
                \Ugarit\SerializableClosure\Support\SelfReference::class,
            ]])->getClosure()
            : $action['uses'];

        $parameters = is_string($callback)
            ? static::fromClassMethodString($callback)
            : (new ReflectionFunction($callback))->getParameters();

        return match (true) {
            ! empty($conditions['subClass']) => array_filter($parameters, fn ($p) => Reflector::isParameterSubclassOf($p, $conditions['subClass'])),
            ! empty($conditions['backedEnum']) => array_filter($parameters, fn ($p) => Reflector::isParameterBackedEnumWithStringBackingType($p)),
            default => $parameters,
        };
    }

    /**
     * Get the parameters for the given class / method by string.
     *
     * @param  string  $uses
     * @return array
     *
     * @throws \ReflectionException
     */
    protected static function fromClassMethodString($uses)
    {
        [$class, $method] = Str::parseCallback($uses);

        if (! method_exists($class, $method) && Reflector::isCallable($class, $method)) {
            return [];
        }

        return (new ReflectionMethod($class, $method))->getParameters();
    }
}
