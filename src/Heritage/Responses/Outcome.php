<?php

declare(strict_types=1);

namespace Heritage\Responses;

use Heritage\Actions\Feedback\Toast;
use Heritage\Contracts\Result\ResultContract;
use Heritage\Contracts\Support\Arrayable;
use Heritage\Contracts\Support\Jsonable;
use Heritage\Contracts\Translation\LocaleResolver;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\ModelTranslation;
use Heritage\Enums\Actions\Feedback\Variant;
use Heritage\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Class Outcome
 *
 * The unified domain execution outcome envelope across the Ugarit Ecosystem.
 * Represents the final result of any Capability or Feature.
 *
 * Key Capabilities:
 * 1. Built from a ResultContract enum, its title, message, variant and status code are read from
 *    the Result when requested, with the current context; explicit values override them.
 * 2. Supports direct static factory creation (Outcome::success, Outcome::failure).
 * 3. Carries the domain data (models, collections); the Controller presents it with a Resource
 *    (withData()). An artifact subclass may add defaultContext() and actions().
 * 4. Builds Toast feedback objects with contextual placeholders.
 * 5. Implements Arrayable, Jsonable, and JsonSerializable for clean API and view delivery.
 */
readonly class Outcome implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * Runtime context overrides such as translation placeholders and entity names.
     *
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * Initialize an Outcome instance.
     *
     * @param  bool  $success  Indicates whether the operation was successful.
     * @param  mixed  $data  The returned payload data.
     * @param  string|null  $message  User-facing feedback message.
     * @param  string|null  $title  User-facing feedback title.
     * @param  Variant|null  $variant  Visual feedback variant (SUCCESS, ERROR, WARNING, INFO).
     * @param  array<string, mixed>  $errors  Validation or error details.
     * @param  array<int, mixed>  $actions  Interactive UI actions.
     * @param  int|null  $statusCode  Explicit HTTP status code override.
     * @param  ResultContract|null  $result  Domain Result enum defining operation state.
     * @param  array<string, mixed>  $context  Runtime context parameters for dynamic placeholder substitution.
     */
    public function __construct(
        public readonly bool $success = true,
        public readonly mixed $data = null,
        public readonly ?string $message = null,
        public readonly ?string $title = null,
        public readonly ?Variant $variant = null,
        public readonly array $errors = [],
        public readonly array $actions = [],
        public readonly ?int $statusCode = null,
        public readonly ?ResultContract $result = null,
        array $context = [],
    ) {
        // Store runtime context internally
        $this->context = $context;
    }

    /**
     * Create an Outcome instance from a domain ResultContract Enum.
     *
     * @param  ResultContract  $result  The domain Result enum.
     * @param  mixed  $data  Payload data.
     * @param  array<string, mixed>  $context  Runtime context parameters.
     * @return static
     */
    public static function fromResult(
        ResultContract $result,
        mixed $data = null,
        array $context = []
    ): static {
        // Title, message, variant and status are read from the Result when requested,
        // so they always reflect the current context.
        return new static(
            success: $result->isSuccess(),
            data: $data,
            result: $result,
            context: $context
        );
    }

    /**
     * Create a successful outcome instance.
     *
     * @param  mixed  $data  Payload data.
     * @param  string|null  $message  User-facing success message.
     * @param  string|null  $title  User-facing success title.
     * @param  array<int, mixed>  $actions  Interactive UI actions.
     * @param  int  $statusCode  HTTP status code (defaults to 200 OK).
     * @param  array<string, mixed>  $context  Runtime context parameters.
     * @return static
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        ?string $title = null,
        array $actions = [],
        int $statusCode = 200,
        array $context = []
    ): static {
        // Construct successful outcome instance
        return new static(
            success: true,
            data: $data,
            message: $message,
            title: $title,
            variant: Variant::SUCCESS,
            errors: [],
            actions: $actions,
            statusCode: $statusCode,
            result: null,
            context: $context
        );
    }

    /**
     * Create a failed outcome instance.
     *
     * @param  string|null  $message  Descriptive failure message.
     * @param  array<string, mixed>  $errors  Validation or error details.
     * @param  mixed  $data  Optional payload data.
     * @param  string|null  $title  User-facing failure title.
     * @param  array<int, mixed>  $actions  Corrective interactive actions.
     * @param  int  $statusCode  HTTP status code (defaults to 400 Bad Request).
     * @param  array<string, mixed>  $context  Runtime context parameters.
     * @return static
     */
    public static function failure(
        ?string $message = null,
        array $errors = [],
        mixed $data = null,
        ?string $title = null,
        array $actions = [],
        int $statusCode = 400,
        array $context = []
    ): static {
        // Construct failed outcome instance
        return new static(
            success: false,
            data: $data,
            message: $message,
            title: $title,
            variant: Variant::ERROR,
            errors: $errors,
            actions: $actions,
            statusCode: $statusCode,
            result: null,
            context: $context
        );
    }

    /**
     * Determine if the outcome represents success.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        // Prioritize Result enum check if available, otherwise check boolean flag
        return $this->result !== null ? $this->result->isSuccess() : $this->success;
    }

    /**
     * Determine if the outcome represents failure.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        // Return opposite of isSuccess()
        return ! $this->isSuccess();
    }

    /**
     * Retrieve the outcome payload data.
     *
     * @return mixed A model, collection or paginator from the domain, or a Resource once presented.
     */
    public function data(): mixed
    {
        return $this->data;
    }

    /**
     * Retrieve the domain value behind the payload, unwrapping a Resource.
     *
     * @return mixed
     */
    public function subject(): mixed
    {
        return $this->data instanceof JsonResource ? $this->data->resource : $this->data;
    }

    /**
     * Return a new Outcome instance carrying the given payload, e.g. the data wrapped in a Resource.
     *
     * @param  mixed  $data  The new payload.
     * @return static
     */
    public function withData(mixed $data): static
    {
        return $this->copyWith(['data' => $data]);
    }

    /**
     * Retrieve the user-facing feedback title.
     *
     * @return string
     */
    public function title(): string
    {
        // Check explicit title, then Result enum title, or fallback to default
        if ($this->title !== null) {
            return $this->title;
        }

        if ($this->result !== null) {
            return $this->result->title($this->getContext());
        }

        return $this->isSuccess() ? 'Success' : 'Error';
    }

    /**
     * Retrieve the user-facing feedback message.
     *
     * @return string
     */
    public function message(): string
    {
        // Check explicit message, then Result enum message, or empty string
        if ($this->message !== null) {
            return $this->message;
        }

        if ($this->result !== null) {
            return $this->result->message($this->getContext());
        }

        return '';
    }

    /**
     * Retrieve the feedback visual variant.
     *
     * @return Variant
     */
    public function variant(): Variant
    {
        // Check explicit variant, then Result enum variant, or fallback
        if ($this->variant !== null) {
            return $this->variant;
        }

        if ($this->result !== null) {
            return $this->result->variant();
        }

        return $this->isSuccess() ? Variant::SUCCESS : Variant::ERROR;
    }

    /**
     * Retrieve the array of errors.
     *
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retrieve the array of interactive actions; override to derive actions from the result and data.
     *
     * @return array<int, mixed>
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * Retrieve the resolved HTTP status code.
     *
     * @return int
     */
    public function statusCode(): int
    {
        // Prioritize explicit status code, then Result enum status code, or fallback
        if ($this->statusCode !== null) {
            return $this->statusCode;
        }

        if ($this->result !== null) {
            return $this->result->statusCode();
        }

        return $this->isSuccess() ? 200 : 400;
    }

    /**
     * Resolve the final merged context used by this Outcome.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->feedbackPlaceholders(array_merge($this->defaultContext(), $this->context));
    }

    /**
     * Context placeholders every outcome of this class provides (e.g. the entity name derived
     * from the data); the context given to the outcome overrides them.
     *
     * @return array<string, mixed>
     */
    protected function defaultContext(): array
    {
        return [];
    }

    /**
     * Fill the name and locale placeholders from the record when the caller did not.
     *
     * A translation outcome otherwise leaves :entity empty and :locale untouched, and a record
     * addressed by its numeric key would show that key where the message expects a name.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function feedbackPlaceholders(array $context): array
    {
        $subject = $this->subject();

        if (! $subject instanceof Model) {
            return $context;
        }

        $record = $subject;
        $localeCode = null;

        if ($subject instanceof ModelTranslation) {
            $localeCode = $subject->getLocaleCode();
            $parent = $subject->parent;

            if ($parent instanceof Model) {
                $record = $parent;
            }
        }

        if ($this->placeholderMissing($context['entity'] ?? null) || $this->placeholderIsKey($context['entity'] ?? null, $record)) {
            $name = $this->displayName($subject) ?? $this->displayName($record);

            if ($name !== null) {
                $context['entity'] = $name;
            }
        }

        if ($localeCode !== null && $this->placeholderMissing($context['locale'] ?? null)) {
            $context['locale'] = $this->localeLabel($localeCode);
        }

        return $context;
    }

    /**
     * The first human label on the model: its translated name, then native name, title or slug.
     */
    protected function displayName(Model $model): ?string
    {
        $attributes = array_unique([
            ...(method_exists($model, 'getTranslatable') ? $model->getTranslatable() : []),
            'name',
            'native_name',
            'title',
            'slug',
        ]);

        foreach ($attributes as $attribute) {
            if (! is_string($attribute) || $attribute === '') {
                continue;
            }

            $value = $this->readableAttribute($model, $attribute);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * A translatable attribute, a stored column, or a get mutator; nothing else, so a missing
     * column is not queried as a relationship.
     */
    protected function readableAttribute(Model $model, string $attribute): mixed
    {
        if (method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($attribute)) {
            return $model->getAttribute($attribute);
        }

        if (array_key_exists($attribute, $model->getAttributes()) || $model->hasGetMutator($attribute)) {
            return $model->getAttribute($attribute);
        }

        return null;
    }

    /**
     * The locale's own name, or its code when the resolver has no label.
     */
    protected function localeLabel(string $code): string
    {
        if (function_exists('app') && app()->bound(LocaleResolver::class)) {
            $resolver = app(LocaleResolver::class);

            if (method_exists($resolver, 'label')) {
                return (string) $resolver->label($code);
            }
        }

        return $code;
    }

    protected function placeholderMissing(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * True when the placeholder is only the record's numeric key.
     */
    protected function placeholderIsKey(mixed $value, Model $model): bool
    {
        return is_scalar($value)
            && (string) $value !== ''
            && (string) $value === (string) $model->getKey();
    }

    /**
     * Return a new Outcome instance with replaced context.
     *
     * @param  array<string, mixed>  $context  The new context array.
     * @return static
     */
    public function withContext(array $context): static
    {
        return $this->copyWith(['context' => $context]);
    }

    /**
     * Return a new Outcome instance with the given errors merged into the existing ones.
     *
     * @param  array<string, mixed>  $errors  Displayable failure reasons.
     * @return static
     */
    public function withErrors(array $errors): static
    {
        return $this->copyWith(['errors' => array_merge($this->errors, $errors)]);
    }

    /**
     * Copy this Outcome with some properties replaced, without calling the constructor, so
     * subclasses with their own constructor signature are copied as they are.
     *
     * @param  array<string, mixed>  $overrides  Property values to replace.
     * @return static
     */
    protected function copyWith(array $overrides): static
    {
        $reflection = new \ReflectionObject($this);
        $copy = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();

            if ($property->isStatic() || (! $property->isInitialized($this) && ! array_key_exists($name, $overrides))) {
                continue;
            }

            $value = array_key_exists($name, $overrides) ? $overrides[$name] : $property->getValue($this);

            // Readonly properties are initialized from the scope of the class declaring them
            \Closure::bind(function () use ($name, $value): void {
                $this->{$name} = $value;
            }, $copy, $property->getDeclaringClass()->getName())();
        }

        return $copy;
    }

    /**
     * Return a new Outcome instance with merged context.
     *
     * @param  array<string, mixed>  $context  The context array to merge.
     * @return static
     */
    public function withMergedContext(array $context): static
    {
        return $this->copyWith(['context' => array_merge($this->context, $context)]);
    }

    /**
     * Generate a structured Toast feedback notification object.
     *
     * @return Toast|null
     */
    public function toast(): ?Toast
    {
        // Build toast notification using resolved variant, title, message, and actions
        return Toast::make(
            variant: $this->variant(),
            title: $this->title(),
            message: $this->message(),
            actions: $this->actions()
        );
    }

    /**
     * Convert the outcome object into an associative array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            // Success indicator
            'success' => $this->isSuccess(),

            // Title
            'title' => $this->title(),

            // Message
            'message' => $this->message(),

            // Visual variant string
            'variant' => $this->variant()->value,

            // Resolve Arrayable data to raw array if applicable
            'data' => $this->data instanceof Arrayable ? $this->data->toArray() : $this->data,

            // Detailed error mapping
            'errors' => $this->errors,

            // Action list
            'actions' => $this->actions(),

            // HTTP status code
            'status_code' => $this->statusCode(),
        ];
    }

    /**
     * Convert the outcome instance into a JSON string.
     *
     * @param  int  $options  json_encode options.
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        // Return standard array representation
        return $this->toArray();
    }
}
