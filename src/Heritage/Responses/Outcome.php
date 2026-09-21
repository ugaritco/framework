<?php

declare(strict_types=1);

namespace Heritage\Responses;

use Heritage\Actions\Feedback\Toast;
use Heritage\Contracts\Result\ResultContract;
use Heritage\Contracts\Support\Arrayable;
use Heritage\Contracts\Support\Jsonable;
use Heritage\Enums\Actions\Feedback\Variant;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Resources\Json\ResourceCollection;
use Heritage\Support\Collection;
use JsonSerializable;

/**
 * Class Outcome
 *
 * The unified domain execution outcome envelope across the Ugarit Ecosystem.
 * Represents the final result of any UseCase, Service, or Feature operation.
 *
 * Key Capabilities:
 * 1. Supports strongly-typed domain ResultContract Enums for rich status and localized messages.
 * 2. Supports direct static factory creation (Outcome::success, Outcome::failure).
 * 3. Encapsulates payload data, error messages, HTTP status codes, and user-facing notifications.
 * 4. Automatically resolves and builds Toast feedback objects with contextual placeholders.
 * 5. Implements Arrayable, Jsonable, and JsonSerializable for clean API and view delivery.
 */
class Outcome implements Arrayable, Jsonable, JsonSerializable
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
     * @param  JsonResource|ResourceCollection|Collection|array|null  $data  The returned payload data.
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
        public readonly JsonResource|ResourceCollection|Collection|array|null $data = null,
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
     * @param  JsonResource|ResourceCollection|Collection|array|null  $data  Payload data.
     * @param  array<string, mixed>  $context  Runtime context parameters.
     * @return static
     */
    public static function fromResult(
        ResultContract $result,
        JsonResource|ResourceCollection|Collection|array|null $data = null,
        array $context = []
    ): static {
        // Construct Outcome deriving success, variant, and status from the Result enum
        return new static(
            success: $result->isSuccess(),
            data: $data,
            message: $result->message($context),
            title: $result->title($context),
            variant: $result->variant(),
            errors: [],
            actions: [],
            statusCode: $result->statusCode(),
            result: $result,
            context: $context
        );
    }

    /**
     * Create a successful outcome instance.
     *
     * @param  JsonResource|ResourceCollection|Collection|array|null  $data  Payload data.
     * @param  string|null  $message  User-facing success message.
     * @param  string|null  $title  User-facing success title.
     * @param  array<int, mixed>  $actions  Interactive UI actions.
     * @param  int  $statusCode  HTTP status code (defaults to 200 OK).
     * @param  array<string, mixed>  $context  Runtime context parameters.
     * @return static
     */
    public static function success(
        JsonResource|ResourceCollection|Collection|array|null $data = null,
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
     * @param  JsonResource|ResourceCollection|Collection|array|null  $data  Optional payload data.
     * @param  string|null  $title  User-facing failure title.
     * @param  array<int, mixed>  $actions  Corrective interactive actions.
     * @param  int  $statusCode  HTTP status code (defaults to 400 Bad Request).
     * @param  array<string, mixed>  $context  Runtime context parameters.
     * @return static
     */
    public static function failure(
        ?string $message = null,
        array $errors = [],
        JsonResource|ResourceCollection|Collection|array|null $data = null,
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
     * @return JsonResource|ResourceCollection|Collection|array|null
     */
    public function data(): mixed
    {
        return $this->data;
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
     * Retrieve the array of interactive actions.
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
        // Return internal context array
        return $this->context;
    }

    /**
     * Return a new Outcome instance with replaced context.
     *
     * @param  array<string, mixed>  $context  The new context array.
     * @return static
     */
    public function withContext(array $context): static
    {
        // Construct new instance with updated context
        return new static(
            success: $this->success,
            data: $this->data,
            message: $this->message,
            title: $this->title,
            variant: $this->variant,
            errors: $this->errors,
            actions: $this->actions,
            statusCode: $this->statusCode,
            result: $this->result,
            context: $context
        );
    }

    /**
     * Return a new Outcome instance with merged context.
     *
     * @param  array<string, mixed>  $context  The context array to merge.
     * @return static
     */
    public function withMergedContext(array $context): static
    {
        // Construct new instance merging existing context with new parameters
        return new static(
            success: $this->success,
            data: $this->data,
            message: $this->message,
            title: $this->title,
            variant: $this->variant,
            errors: $this->errors,
            actions: $this->actions,
            statusCode: $this->statusCode,
            result: $this->result,
            context: array_merge($this->context, $context)
        );
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
            actions: $this->actions
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
            'actions' => $this->actions,

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
