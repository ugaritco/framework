<?php

declare(strict_types=1);

namespace Heritage\Parcels;

use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Database\Eloquent\Collection;
use Heritage\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * Class Parcel
 *
 * Base structured data envelope in the Ugarit Ecosystem.
 *
 * Architectural Features:
 * 1. Type-Safe Data Envelope: Defines boundary structures and secures data transportation across layers.
 * 2. Recursive Reflection Mapping: Automatically constructs Parcels from associative arrays, models, or other Parcels.
 * 3. Multi-Language / i18n Compatibility: Transparently normalizes translation collections.
 * 4. Model Attachment: Attaches model instances to differentiate between Create and Update operations in repositories.
 * 5. Boundary Verification: Provides hasRequiredFieldsFor and hasDataFor helpers for cross-layer data readiness.
 */
abstract class Parcel implements ParcelContract
{
    /**
     * The Eloquent model instance optionally attached to this Parcel (e.g. for update or delete operations).
     *
     * @var Model|null
     */
    protected ?Model $model = null;

    /**
     * Convert the Parcel properties into an associative array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Extract all defined properties from this instance
        return get_object_vars($this);
    }

    /**
     * Convert the Parcel to a JSON string.
     *
     * @param  int  $options  json_encode options.
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        // Encode array representation to JSON
        return (string) json_encode($this->toArray(), $options);
    }

    /**
     * Attach an Eloquent model instance to this Parcel.
     *
     * @param  Model  $model  The Eloquent model instance to attach.
     * @return static
     */
    public function setModel(Model $model): static
    {
        // Set model instance in protected property
        $this->model = $model;

        // Return current instance for fluent chaining
        return $this;
    }

    /**
     * Retrieve the attached Eloquent model instance if available.
     *
     * @return Model|null
     */
    public function getModel(): ?Model
    {
        // Return attached model instance
        return $this->model;
    }

    /**
     * Determine if null values should be filtered out before persisting to database.
     *
     * @return bool
     */
    public function shouldFilterNulls(): bool
    {
        // True by default to prevent unintentional null overwrites on existing columns
        return true;
    }

    /**
     * Check if the Parcel contains any non-empty data fields.
     *
     * @param  array<string>  $exclude  Properties to exclude from the check (e.g. 'id', 'model').
     * @return bool
     */
    public function isNotEmpty(array $exclude = ['id', 'model']): bool
    {
        // Iterate through all properties extracted via toArray()
        foreach ($this->toArray() as $key => $value) {
            // Skip excluded properties
            if (in_array($key, $exclude, true)) {
                continue;
            }

            // Return true if any non-empty value is found
            if (! empty($value)) {
                return true;
            }
        }

        // All non-excluded fields are empty
        return false;
    }

    /**
     * Instantiate a Parcel directly from an Eloquent model instance via reflection.
     *
     * @param  Model  $model  Source Eloquent model instance.
     * @param  bool  $attachModel  Whether to attach the model to the resulting Parcel.
     * @return static
     *
     * @throws ReflectionException
     */
    public static function fromModel(Model $model, bool $attachModel = false): static
    {
        // Extracted data container
        $data = [];

        // Inspect target Parcel class properties using ReflectionClass
        $reflection = new ReflectionClass(static::class);
        $properties = array_map(fn ($prop) => $prop->getName(), $reflection->getProperties());

        // Map matching model attributes to Parcel properties
        foreach ($properties as $property) {
            if (isset($model->{$property})) {
                $value = $model->{$property};

                // Automatically convert Eloquent translation collections to native arrays
                if ($property === 'translations' && $value instanceof Collection) {
                    $value = $value->toArray();
                }

                // Store value in extracted data array
                $data[$property] = $value;
            }
        }

        // Instantiate Parcel via fromArray
        $parcel = static::fromArray($data);

        // Attach model instance if requested and property exists
        if ($attachModel && property_exists($parcel, 'model')) {
            $parcel->setModel($model);
        }

        return $parcel;
    }

    /**
     * Instantiate a new Parcel using data from another Parcel or DTO with optional overrides.
     *
     * @param  object  $source  Source Parcel or DTO instance.
     * @param  array<string, mixed>  $additionalData  Data overrides.
     * @return static
     */
    public static function fromParcel(object $source, array $additionalData = []): static
    {
        $sourceArray = method_exists($source, 'toArray') ? $source->toArray() : get_object_vars($source);

        // Merge source properties with overrides
        $data = array_merge($sourceArray, $additionalData);

        // Instantiate new Parcel
        return static::fromArray($data);
    }

    /**
     * Backward-compatible alias for fromParcel.
     *
     * @param  object  $source
     * @param  array<string, mixed>  $additionalData
     * @return static
     */
    public static function fromDTO(object $source, array $additionalData = []): static
    {
        return static::fromParcel($source, $additionalData);
    }

    /**
     * Check if this Parcel satisfies all required constructor parameters for a target Parcel class.
     *
     * @param  class-string<Parcel>  $targetParcelClass  Target Parcel class name.
     * @param  array<string, mixed>  $additionalData  Supplementary data.
     * @return bool
     */
    public function hasRequiredFieldsFor(string $targetParcelClass, array $additionalData = []): bool
    {
        if (! is_subclass_of($targetParcelClass, self::class)) {
            return false;
        }

        $reflection = new ReflectionClass($targetParcelClass);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return true;
        }

        $data = array_merge($this->toArray(), $additionalData);

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if ($param->isDefaultValueAvailable() || ($type instanceof ReflectionNamedType && $type->allowsNull())) {
                continue;
            }

            if (! isset($data[$name]) || (is_scalar($data[$name]) && trim((string) $data[$name]) === '') || (is_array($data[$name]) && empty($data[$name]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the current Parcel has any non-empty data matching fields of a target Parcel.
     *
     * @param  class-string<Parcel>  $targetParcelClass  Target Parcel class.
     * @param  array<string>  $exclude  Properties to exclude.
     * @return bool
     */
    public function hasDataFor(string $targetParcelClass, array $exclude = ['id', 'model']): bool
    {
        if (! is_subclass_of($targetParcelClass, self::class)) {
            return false;
        }

        $reflection = new ReflectionClass($targetParcelClass);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return false;
        }

        $sourceData = $this->toArray();

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (in_array($name, $exclude, true)) {
                continue;
            }

            if ($param->isDefaultValueAvailable() || ($type instanceof ReflectionNamedType && $type->allowsNull())) {
                continue;
            }

            if (isset($sourceData[$name]) && ! empty($sourceData[$name])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a Parcel instance recursively from an associative array with nested Parcel mapping.
     *
     * Flow:
     * 1. Translation handling: unpack Collection instances to arrays.
     * 2. Inspect constructor signature via Reflection.
     * 3. Nested Parcel recursion: if parameter type is a Parcel subclass, map recursively.
     * 4. Match parameters against array keys, default values, or null.
     * 5. Instantiate and return the new Parcel instance.
     *
     * @param  array<string, mixed>  $data  Associative input data.
     * @return static
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException If a required parameter is missing.
     */
    public static function fromArray(array $data): static
    {
        // Step 1: Handle translation collection serialization
        if (isset($data['translations']) && $data['translations'] instanceof Collection) {
            $data['translations'] = $data['translations']->toArray();
        }

        // Step 2: Inspect class constructor via reflection
        $class = new ReflectionClass(static::class);
        $constructor = $class->getConstructor();

        // Return parameterless instance if no constructor exists
        if (! $constructor) {
            return new static;
        }

        // Parameter values array for instantiation
        $params = [];

        // Step 3: Iterate through constructor parameters
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Handle nested Parcel recursion
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $paramClass = $type->getName();

                // If parameter type extends base Parcel
                if (is_subclass_of($paramClass, self::class)) {
                    $value = $data[$name] ?? null;

                    // If value is an Eloquent model, map from model
                    if ($value instanceof Model) {
                        $params[] = $paramClass::fromModel($value);
                    } elseif (is_array($value)) {
                        // If value is an array, map recursively
                        $params[] = $paramClass::fromArray($value);
                    } else {
                        // Pass value as-is
                        $params[] = $value;
                    }

                    continue;
                }
            }

            // Match parameter value or fallback to defaults
            if (array_key_exists($name, $data)) {
                $params[] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } elseif ($type instanceof ReflectionNamedType && $type->allowsNull()) {
                $params[] = null;
            } else {
                throw new InvalidArgumentException("Missing value for required parameter '{$name}' in ".static::class);
            }
        }

        // Step 4: Instantiate and return the new Parcel with resolved arguments
        return $class->newInstanceArgs($params);
    }
}
