<?php

declare(strict_types=1);

namespace Heritage\DTOs;

use Heritage\Database\Eloquent\Collection;
use Heritage\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * Class DTO
 *
 * Base Data Transfer Object in the Ugarit Ecosystem.
 * A comprehensive, type-safe base Data Transfer Object supporting recursive reflection mapping.
 *
 * Architectural Features:
 * 1. Recursive Reflection Mapping: Automatically constructs DTOs from associative arrays or Eloquent models,
 *    recursively mapping nested DTO structures.
 * 2. Multi-Language / i18n Compatibility: Handles Eloquent translation collections transparently.
 * 3. Schema Verification Helpers: Provides hasRequiredFieldsFor and hasDataFor methods to check cross-DTO data readiness.
 * 4. Model Attachment: Attaches model instances to differentiate between Create and Update operations in repositories.
 */
abstract class DTO
{
    /**
     * The Eloquent model instance optionally attached to this DTO (e.g. for update or delete operations).
     *
     * @var Model|null
     */
    protected ?Model $model = null;

    /**
     * Convert the DTO properties into an associative array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Extract all defined properties from this instance
        return get_object_vars($this);
    }

    /**
     * Convert the DTO to a JSON string.
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
     * Attach an Eloquent model instance to this DTO.
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
     * Check if the DTO contains any non-empty data fields.
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
     * Instantiate a DTO directly from an Eloquent model instance via reflection.
     *
     * @param  Model  $model  Source Eloquent model instance.
     * @param  bool  $attachModel  Whether to attach the model to the resulting DTO.
     * @return static
     *
     * @throws ReflectionException
     */
    public static function fromModel(Model $model, bool $attachModel = false): static
    {
        // Extracted data container
        $data = [];

        // Inspect target DTO class properties using ReflectionClass
        $reflection = new ReflectionClass(static::class);
        $properties = array_map(fn ($prop) => $prop->getName(), $reflection->getProperties());

        // Map matching model attributes to DTO properties
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

        // Instantiate DTO via fromArray
        $dto = static::fromArray($data);

        // Attach model instance if requested and property exists
        if ($attachModel && property_exists($dto, 'model')) {
            $dto->setModel($model);
        }

        return $dto;
    }

    /**
     * Check if this DTO satisfies all required constructor parameters for a target DTO class.
     *
     * @param  class-string<DTO>  $targetDtoClass  Target DTO class name.
     * @param  array<string, mixed>  $additionalData  Supplementary data.
     * @return bool
     */
    public function hasRequiredFieldsFor(string $targetDtoClass, array $additionalData = []): bool
    {
        // Verify target class inherits from base DTO
        if (! is_subclass_of($targetDtoClass, self::class)) {
            return false;
        }

        // Inspect target class constructor parameters
        $reflection = new ReflectionClass($targetDtoClass);
        $constructor = $reflection->getConstructor();

        // If target class has no constructor, requirements are inherently satisfied
        if (! $constructor) {
            return true;
        }

        // Merge current DTO properties with additional data
        $data = array_merge($this->toArray(), $additionalData);

        // Inspect each constructor parameter
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Skip parameter if it has a default value or allows null
            if ($param->isDefaultValueAvailable() || ($type instanceof ReflectionNamedType && $type->allowsNull())) {
                continue;
            }

            // Return false if a required non-nullable parameter is missing or empty
            if (! isset($data[$name]) || (is_scalar($data[$name]) && trim((string) $data[$name]) === '') || (is_array($data[$name]) && empty($data[$name]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the current DTO has any non-empty data matching fields of a target DTO.
     *
     * @param  class-string<DTO>  $targetDtoClass  Target DTO class.
     * @param  array<string>  $exclude  Properties to exclude.
     * @return bool
     */
    public function hasDataFor(string $targetDtoClass, array $exclude = ['id', 'model']): bool
    {
        if (! is_subclass_of($targetDtoClass, self::class)) {
            return false;
        }

        $reflection = new ReflectionClass($targetDtoClass);
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
     * Instantiate a new DTO using data from another DTO with optional overrides.
     *
     * @param  DTO  $dto  Source DTO instance.
     * @param  array<string, mixed>  $additionalData  Data overrides.
     * @return static
     */
    public static function fromDTO(DTO $dto, array $additionalData = []): static
    {
        // Merge source DTO properties with overrides
        $data = array_merge($dto->toArray(), $additionalData);

        // Instantiate new DTO
        return static::fromArray($data);
    }

    /**
     * Build a DTO instance recursively from an associative array with nested DTO mapping.
     *
     * Flow:
     * 1. Translation handling: unpack Collection instances to arrays.
     * 2. Inspect constructor signature via Reflection.
     * 3. Nested DTO recursion: if parameter type is a DTO subclass, map recursively.
     * 4. Match parameters against array keys, default values, or null.
     * 5. Instantiate and return the new DTO instance.
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

            // Handle nested DTO recursion
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $paramClass = $type->getName();

                // If parameter type extends base DTO
                if (is_subclass_of($paramClass, DTO::class)) {
                    $value = $data[$name] ?? null;

                    // If value is an Eloquent model, map from model
                    if ($value instanceof Model) {
                        $params[] = $paramClass::fromModel($value);
                    } elseif (is_array($value)) {
                        // If value is an array, map recursively
                        $params[] = $paramClass::fromArray($value);
                    } else {
                        // Pass value as-is (e.g. already a DTO or null)
                        $params[] = $value;
                    }

                    continue;
                }
            }

            // Match parameter value or fallback to defaults
            if (array_key_exists($name, $data)) {
                // Key exists in input data
                $params[] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                // Use constructor parameter default value
                $params[] = $param->getDefaultValue();
            } elseif ($type instanceof ReflectionNamedType && $type->allowsNull()) {
                // Parameter is nullable
                $params[] = null;
            } else {
                // Throw explicit exception when required non-nullable parameter is missing
                throw new InvalidArgumentException("Missing value for required parameter '{$name}' in ".static::class);
            }
        }

        // Step 4: Instantiate and return the new DTO with resolved arguments
        return $class->newInstanceArgs($params);
    }
}
