<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

use Heritage\Contracts\Result\ResultContract;
use Heritage\Outcomes\Outcome;
use LogicException;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * The state of a flow: the named results of succeeded steps, the record of every step,
 * and the flow's overall status. A failed or skipped step has no result.
 *
 *     $state->result('branch');        // the succeeded step's data (LogicException otherwise)
 *     $state->hasSucceeded('branch');
 *     $state->record('notify')->status();
 *     $state->isSkipped('audit');
 */
final class FlowState
{
    /**
     * The overall status.
     */
    private FlowStatus $status = FlowStatus::PENDING;

    /**
     * The step records, in definition order.
     *
     * @var array<string, StepRecord>
     */
    private array $records = [];

    /**
     * The data of succeeded steps, by name.
     *
     * @var array<string, mixed>
     */
    private array $results = [];

    /**
     * The record that halted the flow (an each() item's record for an each() step).
     */
    private ?StepRecord $failure = null;

    /**
     * The exception that halted the flow, if any.
     */
    private ?Throwable $exception = null;

    /**
     * @param  array<int, StepRecord>  $records  A pending record per defined step.
     */
    public function __construct(array $records = [])
    {
        foreach ($records as $record) {
            $this->records[$record->name] = $record;
        }
    }

    /**
     * The overall status.
     *
     * @return FlowStatus
     */
    public function status(): FlowStatus
    {
        return $this->status;
    }

    /**
     * Whether the flow completed (optional steps may have failed).
     *
     * @return bool
     */
    public function completed(): bool
    {
        return $this->status === FlowStatus::COMPLETED;
    }

    /**
     * Whether the flow was halted.
     *
     * @return bool
     */
    public function halted(): bool
    {
        return $this->status === FlowStatus::HALTED;
    }

    /**
     * The data of a succeeded step (an each() step: its succeeded items' data by key).
     *
     * @param  string  $name  The step name.
     * @return mixed
     *
     * @throws LogicException When the step is unknown or did not succeed.
     */
    public function result(string $name): mixed
    {
        $record = $this->record($name);

        if (! $record->succeeded()) {
            throw new LogicException("Flow step [{$name}] has no result: it is {$record->status()->value}.");
        }

        return $this->results[$name];
    }

    /**
     * The data of a succeeded step, or a default when it did not succeed.
     *
     * @param  string  $name  The step name.
     * @param  mixed  $default  Returned when the step did not succeed.
     * @return mixed
     */
    public function resultOr(string $name, mixed $default = null): mixed
    {
        return $this->record($name)->succeeded() ? $this->results[$name] : $default;
    }

    /**
     * The outcome a step's capability returned, successful or not.
     *
     * @param  string  $name  The step name.
     * @return Outcome|null
     */
    public function outcome(string $name): ?Outcome
    {
        return $this->record($name)->outcome();
    }

    /**
     * A step's record.
     *
     * @param  string  $name  The step name.
     * @return StepRecord
     *
     * @throws LogicException When the step is unknown.
     */
    public function record(string $name): StepRecord
    {
        return $this->records[$name] ?? throw new LogicException("Unknown flow step [{$name}].");
    }

    /**
     * Every step record, in definition order.
     *
     * @return array<string, StepRecord>
     */
    public function records(): array
    {
        return $this->records;
    }

    /**
     * Whether a step succeeded.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasSucceeded(string $name): bool
    {
        return $this->record($name)->succeeded();
    }

    /**
     * Whether a step failed.
     *
     * @param  string  $name
     * @return bool
     */
    public function isFailed(string $name): bool
    {
        return $this->record($name)->failed();
    }

    /**
     * Whether a step was skipped.
     *
     * @param  string  $name
     * @return bool
     */
    public function isSkipped(string $name): bool
    {
        return $this->record($name)->skipped();
    }

    /**
     * Whether a step was not evaluated.
     *
     * @param  string  $name
     * @return bool
     */
    public function isPending(string $name): bool
    {
        return $this->record($name)->pending();
    }

    /**
     * The record that halted the flow (an each() item's record for an each() step).
     *
     * @return StepRecord|null
     */
    public function failure(): ?StepRecord
    {
        return $this->failure;
    }

    /**
     * The result of the halting failure.
     *
     * @return ResultContract|null
     */
    public function failureResult(): ?ResultContract
    {
        return $this->failure?->result();
    }

    /**
     * The displayable reasons of the halting failure.
     *
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->failure?->errors() ?? [];
    }

    /**
     * The exception that halted the flow, if any.
     *
     * @return Throwable|null
     */
    public function exception(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Every step's trace (states, reasons, errors; no domain data), for debugging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'failure' => $this->failure?->key === null ? $this->failure?->name : "{$this->failure->name}.{$this->failure->key}",
            'steps' => array_map(fn (StepRecord $record) => $record->toArray(), $this->records),
        ];
    }

    /**
     * Assert that a step succeeded.
     *
     * @param  string  $name
     * @return $this
     */
    public function assertSucceeded(string $name): self
    {
        Assert::assertSame(StepStatus::SUCCEEDED, $this->record($name)->status(), "Flow step [{$name}] did not succeed.");

        return $this;
    }

    /**
     * Assert that a step failed.
     *
     * @param  string  $name
     * @return $this
     */
    public function assertFailed(string $name): self
    {
        Assert::assertSame(StepStatus::FAILED, $this->record($name)->status(), "Flow step [{$name}] did not fail.");

        return $this;
    }

    /**
     * Assert that a step was skipped, optionally for a given reason.
     *
     * @param  string  $name
     * @param  SkipReason|null  $reason
     * @return $this
     */
    public function assertSkipped(string $name, ?SkipReason $reason = null): self
    {
        $record = $this->record($name);

        Assert::assertSame(StepStatus::SKIPPED, $record->status(), "Flow step [{$name}] was not skipped.");

        if ($reason !== null) {
            Assert::assertSame($reason, $record->reason(), "Flow step [{$name}] was skipped for another reason.");
        }

        return $this;
    }

    /**
     * Assert that a step was not evaluated.
     *
     * @param  string  $name
     * @return $this
     */
    public function assertPending(string $name): self
    {
        Assert::assertSame(StepStatus::PENDING, $this->record($name)->status(), "Flow step [{$name}] is not pending.");

        return $this;
    }

    /**
     * Assert that the flow completed.
     *
     * @return $this
     */
    public function assertCompleted(): self
    {
        Assert::assertSame(FlowStatus::COMPLETED, $this->status, 'The flow did not complete.');

        return $this;
    }

    /**
     * Assert that the flow was halted, optionally by a given step.
     *
     * @param  string|null  $by  The step that halted it.
     * @return $this
     */
    public function assertHalted(?string $by = null): self
    {
        Assert::assertSame(FlowStatus::HALTED, $this->status, 'The flow was not halted.');

        if ($by !== null) {
            Assert::assertSame($by, $this->failure?->name, "The flow was not halted by [{$by}].");
        }

        return $this;
    }

    /**
     * Read a succeeded step's result as a property ($state->branch).
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->result($name);
    }

    /**
     * Mark the flow as running.
     *
     * @internal
     *
     * @return void
     */
    public function start(): void
    {
        $this->status = FlowStatus::RUNNING;
    }

    /**
     * Store a succeeded step's result.
     *
     * @internal
     *
     * @param  string  $name
     * @param  mixed  $result
     * @return void
     */
    public function put(string $name, mixed $result): void
    {
        $this->results[$name] = $result;
    }

    /**
     * Mark the flow as completed.
     *
     * @internal
     *
     * @return void
     */
    public function complete(): void
    {
        $this->status = FlowStatus::COMPLETED;
    }

    /**
     * Mark the flow as halted by a record and/or an exception.
     *
     * @internal
     *
     * @param  StepRecord|null  $failure  The record that halted it.
     * @param  Throwable|null  $exception  The exception that halted it.
     * @return void
     */
    public function halt(?StepRecord $failure, ?Throwable $exception = null): void
    {
        $this->status = FlowStatus::HALTED;
        $this->failure = $failure;
        $this->exception = $exception;
    }
}
