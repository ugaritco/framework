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
 * Context-aware base Response for UseCase operations.
 *
 * This class introduces a lightweight "Context" mechanism that allows
 * passing runtime metadata alongside the Result and optional data.
 *
 * The Context is typically used for:
 * - Translation placeholders (e.g. ['name' => 'Muath'])
 * - Dynamic values injected into messages or titles
 * - Per-response overrides without mutating the Result itself
 */
abstract readonly class Response extends Outcome
{
    /**
     * Constructor
     *
     * @param  ResultContract|null  $result  Domain Result enum defining operation state.
     * @param  JsonResource|ResourceCollection|Collection|array|null  $data  Payload data.
     * @param  array<string, mixed>  $context  Runtime context overrides.
     */
    public function __construct(
        ?ResultContract $result = null,
        JsonResource|ResourceCollection|Collection|array|null $data = null,
        array $context = [],
    ) {
        parent::__construct(
            success: $result?->isSuccess() ?? true,
            data: $data,
            message: $result?->message($context) ?? null,
            title: $result?->title($context) ?? null,
            variant: $result?->variant() ?? Variant::SUCCESS,
            errors: [],
            actions: [],
            statusCode: $result?->statusCode() ?? 200,
            result: $result,
            context: $context
        );
    }

    /**
     * Resolve the final Context used by this Response.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->feedbackPlaceholders(array_merge($this->defaultContext(), $this->context));
    }

    /**
     * Return a new response instance with replaced context.
     */
    public function withContext(array $context): static
    {
        return new static(
            $this->result,
            $this->data,
            $context
        );
    }

    /**
     * Return a new response instance with merged context.
     */
    public function withMergedContext(array $context): static
    {
        return new static(
            $this->result,
            $this->data,
            array_merge($this->context, $context)
        );
    }

    /**
     * Default Context for this Response subclass.
     *
     * @return array<string, mixed>
     */
    protected function defaultContext(): array
    {
        return [];
    }

    /**
     * Get the HTTP status code associated with the operation result.
     */
    public function statusCode(): int
    {
        return $this->result?->statusCode() ?? 200;
    }

    /**
     * Get the user-facing feedback title associated with the operation result.
     */
    public function title(): string
    {
        return $this->result?->title($this->getContext()) ?? '';
    }

    /**
     * Get the user-facing feedback message associated with the operation result.
     */
    public function message(): string
    {
        return $this->result?->message($this->getContext()) ?? '';
    }

    /**
     * Get the visual feedback variant associated with the operation result.
     */
    public function variant(): Variant
    {
        return $this->result?->variant() ?? Variant::SUCCESS;
    }

    /**
     * Determine if the operation was successful.
     */
    public function isSuccess(): bool
    {
        return $this->result?->isSuccess() ?? true;
    }

    /**
     * Generate Toast feedback notification object.
     */
    public function toast(): ?Toast
    {
        return Toast::make(
            variant: $this->variant(),
            title: $this->title(),
            message: $this->message(),
            actions: $this->actionMap() ?? []
        );
    }

    /**
     * Each Response subclass defines its own Result -> Actions mapping.
     *
     * @return array<int, mixed>
     */
    protected function actionMap(): array
    {
        return [];
    }

    /**
     * Convert the response instance to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'data' => $this->data instanceof Arrayable ? $this->data->toArray() : $this->data,
            'message' => $this->message(),
            'title' => $this->title(),
            'variant' => $this->variant()->value,
            'statusCode' => $this->statusCode(),
            'toast' => $this->toast()?->toArray(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return (string) json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

