<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

use Heritage\Contracts\Result\ResultContract;
use Heritage\Outcomes\Outcome;
use Throwable;

/**
 * The execution record of a flow step, or of one item of an each() step (with its key).
 *
 * Written by the Flow only; read through FlowState.
 */
final class StepRecord
{
    /**
     * The step's state.
     */
    private StepStatus $status = StepStatus::PENDING;

    /**
     * Why the step was skipped.
     */
    private ?SkipReason $reason = null;

    /**
     * The step that caused the skip (a dependency's name).
     */
    private ?string $cause = null;

    /**
     * The outcome returned by the capability, successful or not.
     */
    private ?Outcome $outcome = null;

    /**
     * The failure result: the step's failWith, or the failed outcome's own result.
     */
    private ?ResultContract $result = null;

    /**
     * The displayable failure reasons.
     *
     * @var array<string, mixed>
     */
    private array $errors = [];

    /**
     * The exception that failed the step, if any.
     */
    private ?Throwable $exception = null;

    /**
     * The records of an each() step's items, keyed by item key.
     *
     * @var array<int|string, StepRecord>
     */
    private array $items = [];

    /**
     * @param  string  $name  The step name.
     * @param  int|string|null  $key  The item key of an each() item, null for a step.
     * @param  bool  $optional  Whether the step's failure lets the flow continue.
     */
    public function __construct(
        public readonly string $name,
        public readonly int|string|null $key = null,
        public readonly bool $optional = false,
    ) {}

    /**
     * The step's state.
     *
     * @return StepStatus
     */
    public function status(): StepStatus
    {
        return $this->status;
    }

    /**
     * Whether the step succeeded.
     *
     * @return bool
     */
    public function succeeded(): bool
    {
        return $this->status === StepStatus::SUCCEEDED;
    }

    /**
     * Whether the step failed.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->status === StepStatus::FAILED;
    }

    /**
     * Whether the step was skipped.
     *
     * @return bool
     */
    public function skipped(): bool
    {
        return $this->status === StepStatus::SKIPPED;
    }

    /**
     * Whether the step was not evaluated.
     *
     * @return bool
     */
    public function pending(): bool
    {
        return $this->status === StepStatus::PENDING;
    }

    /**
     * Why the step was skipped.
     *
     * @return SkipReason|null
     */
    public function reason(): ?SkipReason
    {
        return $this->reason;
    }

    /**
     * The dependency that caused the skip.
     *
     * @return string|null
     */
    public function cause(): ?string
    {
        return $this->cause;
    }

    /**
     * The outcome returned by the capability (null when skipped, pending, or an exception was raised).
     *
     * @return Outcome|null
     */
    public function outcome(): ?Outcome
    {
        return $this->outcome;
    }

    /**
     * The failure result: the step's failWith, or the failed outcome's result.
     *
     * @return ResultContract|null
     */
    public function result(): ?ResultContract
    {
        return $this->result;
    }

    /**
     * The displayable failure reasons.
     *
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * The exception that failed the step.
     *
     * @return Throwable|null
     */
    public function exception(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * The records of an each() step's items, keyed by item key.
     *
     * @return array<int|string, StepRecord>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Mark the step as succeeded with its outcome.
     *
     * @internal
     *
     * @param  Outcome|null  $outcome  The capability's outcome (null for an each() step).
     * @return void
     */
    public function markSucceeded(?Outcome $outcome): void
    {
        $this->status = StepStatus::SUCCEEDED;
        $this->outcome = $outcome;
    }

    /**
     * Mark the step as failed.
     *
     * @internal
     *
     * @param  Outcome|null  $outcome  The failed outcome, if the capability returned one.
     * @param  ResultContract|null  $result  The failure result.
     * @param  array<string, mixed>  $errors  The displayable failure reasons.
     * @param  Throwable|null  $exception  The exception that failed the step.
     * @return void
     */
    public function markFailed(?Outcome $outcome, ?ResultContract $result, array $errors = [], ?Throwable $exception = null): void
    {
        $this->status = StepStatus::FAILED;
        $this->outcome = $outcome;
        $this->result = $result;
        $this->errors = $errors;
        $this->exception = $exception;
    }

    /**
     * Mark the step as skipped.
     *
     * @internal
     *
     * @param  SkipReason  $reason  Why it was skipped.
     * @param  string|null  $cause  The dependency that caused it.
     * @return void
     */
    public function markSkipped(SkipReason $reason, ?string $cause = null): void
    {
        $this->status = StepStatus::SKIPPED;
        $this->reason = $reason;
        $this->cause = $cause;
    }

    /**
     * Set the records of an each() step's items.
     *
     * @internal
     *
     * @param  array<int|string, StepRecord>  $items
     * @return void
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * The record as an array for tracing (no domain data).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'key' => $this->key,
            'status' => $this->status->value,
            'optional' => $this->optional ?: null,
            'reason' => $this->reason?->value,
            'cause' => $this->cause,
            'result' => $this->result instanceof \UnitEnum ? $this->result->name : null,
            'errors' => $this->errors ?: null,
            'exception' => $this->exception ? $this->exception::class : null,
            'items' => $this->items ? array_map(fn (StepRecord $item) => $item->toArray(), $this->items) : null,
        ], fn ($value) => $value !== null);
    }
}
