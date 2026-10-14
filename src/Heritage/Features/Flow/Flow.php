<?php

declare(strict_types=1);

namespace Heritage\Features\Flow;

use Closure;
use Heritage\Auth\Access\AuthorizationException;
use Heritage\Capabilities\Capability;
use Heritage\Container\Container;
use Heritage\Contracts\Capabilities\CapabilityContract;
use Heritage\Contracts\Features\FeatureContract;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Contracts\Result\ResultContract;
use Heritage\Database\ConnectionInterface;
use Heritage\Database\Eloquent\ModelNotFoundException;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;
use Heritage\Support\Facades\DB;
use Heritage\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * A Feature's workflow: named steps running capabilities in order, inside one transaction.
 *
 *     return $this->flow(BranchOutcome::class, Result::FAILED)
 *         ->step('branch', SaveBranchCapability::class, $parcel, failWith: Result::FAILED_MODEL_SAVE)
 *         ->each('translations', $translations, fn ($attributes, $locale, FlowState $state) => Call::to(...))
 *         ->step('notify', NotifyCapability::class, fn (FlowState $state) => ..., optional: true)
 *         ->step('audit', AuditCapability::class, fn (FlowState $state) => ..., needs: 'notify')
 *         ->finish(fn (FlowState $state) => BranchOutcome::fromResult(Result::SUCCESS, $state->branch));
 *
 * A halted flow returns BranchOutcome::fromResult(<the failure's result>)->withErrors(<its errors>);
 * onFailure() replaces it.
 *
 * Execution contract:
 * - Steps run in definition order. For each step: its needs are checked (each must have
 *   SUCCEEDED, else SKIPPED for DEPENDENCY_FAILED / DEPENDENCY_SKIPPED), then its when()
 *   condition (false: SKIPPED for CONDITION_NOT_MET), then it runs.
 * - A step is required by default: its failure halts the flow, rolls the transaction back
 *   and returns onFailure()'s outcome; later steps stay PENDING and finish() is not called.
 * - An optional step runs in a savepoint: its failure rolls back its own changes only,
 *   is recorded, and the flow continues.
 * - An exception raised by the flow's own callbacks (parcel, items, when, context, finish)
 *   is a bug: it halts the flow, is reported, and goes to onFailure() (rethrown without one),
 *   even in an optional step.
 * - HTTP, authorization, validation and not-found exceptions, and a misuse of a step's
 *   perform() (a Parcel it does not accept, or no perform()), roll back and are rethrown.
 * - finish() builds the outcome before the commit; its failure rolls the flow back.
 * - Side effects outside the database (mail, notifications, HTTP calls) are not undone by
 *   a rollback: dispatch them after commit (queued jobs or events with afterCommit).
 */
class Flow
{
    /**
     * Exceptions rethrown after the rollback instead of going to onFailure().
     *
     * @var array<int, class-string<Throwable>>
     */
    protected array $propagate = [
        HttpExceptionInterface::class,
        AuthorizationException::class,
        ValidationException::class,
        ModelNotFoundException::class,
    ];

    /**
     * The step definitions, by name, in definition order.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $steps = [];

    /**
     * Builds the outcome of a halted flow.
     */
    private ?Closure $onFailure = null;

    /**
     * Whether the flow runs inside a transaction.
     */
    private bool $transactional = true;

    /**
     * The database connection of the transaction (null: the default connection).
     */
    private ?string $connection = null;

    /**
     * The record being evaluated, failed when an exception is raised.
     */
    private ?StepRecord $current = null;

    /**
     * @param  class-string<Outcome>|null  $outcome  The outcome of a halted flow without onFailure(): built
     *                                               from the failure's result, with the flow's errors.
     * @param  ResultContract|null  $failWith  The failure result of steps without their own failWith.
     *
     * @throws LogicException When the outcome is not an Outcome class.
     */
    public function __construct(
        private ?string $outcome = null,
        private ?ResultContract $failWith = null,
    ) {
        if ($outcome !== null && ! is_a($outcome, Outcome::class, true)) {
            throw new LogicException("Flow outcome [{$outcome}] must extend ".Outcome::class.'.');
        }
    }

    /**
     * Define a step running a capability (or feature).
     *
     * @param  string  $name  Unique step name; its result is read with $state->result($name).
     * @param  class-string<CapabilityContract|FeatureContract>|CapabilityContract|FeatureContract  $target  What to run.
     * @param  (Closure(FlowState): (Parcel|ParcelContract|null))|Parcel|ParcelContract|null  $parcel  Its input, or a closure building it from the state when the step runs.
     * @param  bool  $optional  Whether its failure lets the flow continue.
     * @param  string|array<int, string>  $needs  Earlier steps that must have succeeded.
     * @param  (Closure(FlowState): bool)|null  $when  Runs the step only when it returns true.
     * @param  ResultContract|null  $failWith  The failure result (default: the flow's failWith, else the failed outcome's result).
     * @param  string|null  $errorKey  Adds the failed outcome's message to the errors under this key.
     * @param  array<string, mixed>|(Closure(FlowState, int|string|null): array<string, mixed>)  $context  Context of that message.
     * @return $this
     *
     * @throws LogicException When the name is taken or a need is not an earlier step.
     */
    public function step(
        string $name,
        string|CapabilityContract|FeatureContract $target,
        Closure|Parcel|ParcelContract|null $parcel = null,
        bool $optional = false,
        string|array $needs = [],
        ?Closure $when = null,
        ?ResultContract $failWith = null,
        ?string $errorKey = null,
        array|Closure $context = [],
    ): static {
        return $this->define($name, [
            'each' => false,
            'target' => $target,
            'parcel' => $parcel,
        ], $optional, $needs, $when, $failWith, $errorKey, $context);
    }

    /**
     * Define a step running a call per item; each item has its own record, keyed by item key.
     *
     * A required each() halts at its first failed item (later items stay PENDING); an optional
     * one runs every item and fails when any item failed. No items: SUCCEEDED with an empty result.
     * Its result is the succeeded items' data by key.
     *
     * @param  string  $name  Unique step name.
     * @param  iterable<int|string, mixed>|(Closure(FlowState): iterable<int|string, mixed>)  $items  The items, or a closure returning them when the step runs.
     * @param  Closure(mixed, int|string, FlowState): Call  $call  Builds the call of an item.
     * @param  bool  $optional  Whether its failure lets the flow continue.
     * @param  string|array<int, string>  $needs  Earlier steps that must have succeeded.
     * @param  (Closure(FlowState): bool)|null  $when  Runs the step only when it returns true.
     * @param  ResultContract|null  $failWith  The failure result (a Call's failWith overrides it).
     * @param  string|null  $errorKey  Error key of a failed item; "{key}" is replaced by the item key.
     * @param  array<string, mixed>|(Closure(FlowState, int|string|null): array<string, mixed>)  $context  Context of the failed item's message.
     * @return $this
     *
     * @throws LogicException When the name is taken or a need is not an earlier step.
     */
    public function each(
        string $name,
        iterable|Closure $items,
        Closure $call,
        bool $optional = false,
        string|array $needs = [],
        ?Closure $when = null,
        ?ResultContract $failWith = null,
        ?string $errorKey = null,
        array|Closure $context = [],
    ): static {
        return $this->define($name, [
            'each' => true,
            'items' => $items,
            'call' => $call,
        ], $optional, $needs, $when, $failWith, $errorKey, $context);
    }

    /**
     * Build the outcome of a halted flow. Without it, the flow's outcome class is built from the
     * failure's result with the errors, or else the failed step's outcome is returned (with
     * failWith's result and the errors); an exception without a result to build from is rethrown.
     *
     * @param  Closure(FlowState): Outcome  $callback
     * @return $this
     */
    public function onFailure(Closure $callback): static
    {
        $this->onFailure = $callback;

        return $this;
    }

    /**
     * Run the steps without a transaction (and without savepoints): a halted flow keeps
     * the changes of the steps that ran.
     *
     * @return $this
     */
    public function withoutTransaction(): static
    {
        $this->transactional = false;

        return $this;
    }

    /**
     * Run the transaction on another database connection.
     *
     * @param  string|null  $name
     * @return $this
     */
    public function connection(?string $name): static
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Run the flow and build its outcome; the returned outcome carries the FlowState ($outcome->flow()).
     *
     * @param  Closure(FlowState): Outcome  $callback  Builds the outcome once every step was evaluated, before the commit.
     * @return Outcome
     *
     * @throws Throwable A propagated exception, or a bug when no onFailure() is set.
     */
    public function finish(Closure $callback): Outcome
    {
        $state = new FlowState(array_map(
            fn (array $step) => new StepRecord($step['name'], null, $step['optional']),
            array_values($this->steps)
        ));

        $state->start();

        try {
            return $this->transactional
                ? $this->database()->transaction(fn () => $this->run($state, $callback))
                : $this->run($state, $callback);
        } catch (FlowHalted $halted) {
            $state->halt($halted->record);

            return $this->failed($state);
        } catch (Throwable $e) {
            if ($this->current !== null && ! $this->current->failed()) {
                $this->current->markFailed(null, $this->failWith, [], $e);
            }

            $state->halt($this->current, $e);

            if (Capability::isMisuse($e)) {
                throw $e;
            }

            foreach ($this->propagate as $class) {
                if ($e instanceof $class) {
                    throw $e;
                }
            }

            if (function_exists('report')) {
                report($e);
            }

            if ($this->onFailure === null && ($this->outcome === null || $this->failureResult($state) === null)) {
                throw $e;
            }

            return $this->failed($state);
        }
    }

    /**
     * Store a step definition after checking its name and needs.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $definition
     * @param  bool  $optional
     * @param  string|array<int, string>  $needs
     * @param  Closure|null  $when
     * @param  ResultContract|null  $failWith
     * @param  string|null  $errorKey
     * @param  array<string, mixed>|Closure  $context
     * @return $this
     *
     * @throws LogicException When the name is taken or a need is not an earlier step.
     */
    private function define(string $name, array $definition, bool $optional, string|array $needs, ?Closure $when, ?ResultContract $failWith, ?string $errorKey, array|Closure $context): static
    {
        if (isset($this->steps[$name])) {
            throw new LogicException("Flow step [{$name}] is already defined.");
        }

        $needs = (array) $needs;

        foreach ($needs as $need) {
            if (! isset($this->steps[$need])) {
                throw new LogicException("Flow step [{$name}] needs [{$need}], which is not an earlier step.");
            }
        }

        $this->steps[$name] = [
            ...$definition,
            'name' => $name,
            'optional' => $optional,
            'needs' => $needs,
            'when' => $when,
            'failWith' => $failWith,
            'errorKey' => $errorKey,
            'context' => $context,
        ];

        return $this;
    }

    /**
     * Evaluate every step, then build the outcome.
     *
     * @param  FlowState  $state
     * @param  Closure  $finish
     * @return Outcome
     *
     * @throws FlowHalted When a required step fails.
     * @throws LogicException When finish() does not return an Outcome.
     */
    private function run(FlowState $state, Closure $finish): Outcome
    {
        foreach ($this->steps as $step) {
            $this->runStep($state, $step);
        }

        $this->current = null;
        $outcome = $finish($state);

        if (! $outcome instanceof Outcome) {
            throw new LogicException('Flow finish() must return an '.Outcome::class.'.');
        }

        $state->complete();

        return $outcome->withFlow($state);
    }

    /**
     * Evaluate one step: needs, condition, then its call(s).
     *
     * @param  FlowState  $state
     * @param  array<string, mixed>  $step
     * @return void
     *
     * @throws FlowHalted When the step is required and fails.
     */
    private function runStep(FlowState $state, array $step): void
    {
        $record = $state->record($step['name']);
        $this->current = $record;

        foreach ($step['needs'] as $need) {
            $dependency = $state->record($need);

            if (! $dependency->succeeded()) {
                $record->markSkipped($dependency->failed() ? SkipReason::DEPENDENCY_FAILED : SkipReason::DEPENDENCY_SKIPPED, $need);

                return;
            }
        }

        if ($step['when'] !== null && ! ($step['when'])($state)) {
            $record->markSkipped(SkipReason::CONDITION_NOT_MET);

            return;
        }

        if (! $step['each']) {
            $parcel = $step['parcel'] instanceof Closure ? ($step['parcel'])($state) : $step['parcel'];
            $outcome = $this->call($state, $record, Call::to($step['target'], $parcel), $step, null);

            if ($record->succeeded()) {
                $state->put($step['name'], $outcome->data);
            } elseif (! $step['optional']) {
                throw new FlowHalted($record);
            }

            return;
        }

        $this->runEach($state, $record, $step);
    }

    /**
     * Evaluate the items of an each() step.
     *
     * @param  FlowState  $state
     * @param  StepRecord  $record  The step's record.
     * @param  array<string, mixed>  $step
     * @return void
     *
     * @throws FlowHalted When the step is required and an item fails.
     * @throws LogicException When the call closure does not return a Call.
     */
    private function runEach(FlowState $state, StepRecord $record, array $step): void
    {
        $items = $step['items'] instanceof Closure ? ($step['items'])($state) : $step['items'];
        $items = is_array($items) ? $items : iterator_to_array($items);

        $records = [];

        foreach (array_keys($items) as $key) {
            $records[$key] = new StepRecord($step['name'], $key, $step['optional']);
        }

        $record->setItems($records);

        $results = [];
        $firstFailure = null;

        foreach ($items as $key => $item) {
            $this->current = $records[$key];
            $call = ($step['call'])($item, $key, $state);

            if (! $call instanceof Call) {
                throw new LogicException("Flow step [{$step['name']}] must return a ".Call::class.' per item.');
            }

            $outcome = $this->call($state, $records[$key], $call, $step, $key);

            if ($records[$key]->succeeded()) {
                $results[$key] = $outcome->data;

                continue;
            }

            $firstFailure ??= $records[$key];

            if (! $step['optional']) {
                $this->current = $record;
                $record->markFailed(null, $firstFailure->result(), $firstFailure->errors());

                throw new FlowHalted($firstFailure);
            }
        }

        $this->current = $record;

        if ($firstFailure !== null) {
            $record->markFailed(null, $firstFailure->result(), $firstFailure->errors());

            return;
        }

        $record->markSucceeded(null);
        $state->put($step['name'], $results);
    }

    /**
     * Run a call and record its outcome; an optional step's call runs in a savepoint.
     *
     * @param  FlowState  $state
     * @param  StepRecord  $record  The record of the step or item.
     * @param  Call  $call
     * @param  array<string, mixed>  $step
     * @param  int|string|null  $key  The item key of an each() item.
     * @return Outcome
     *
     * @throws Throwable The exception the capability raised, after the savepoint rollback.
     */
    private function call(FlowState $state, StepRecord $record, Call $call, array $step, int|string|null $key): Outcome
    {
        $target = is_string($call->target) ? Container::getInstance()->make($call->target) : $call->target;

        if (! $target instanceof CapabilityContract && ! $target instanceof FeatureContract) {
            throw new LogicException("Flow step [{$step['name']}] must run a capability or a feature.");
        }

        $failWith = $call->failWith ?? $step['failWith'] ?? $this->failWith;
        $savepoint = $step['optional'] && $this->transactional;

        if ($savepoint) {
            $this->database()->beginTransaction();
        }

        try {
            $outcome = $target->handle($call->parcel);
        } catch (Throwable $e) {
            if ($savepoint) {
                $this->database()->rollBack();
            }

            $record->markFailed(null, $failWith, [], $e);

            throw $e;
        }

        if ($outcome->isSuccess()) {
            if ($savepoint) {
                $this->database()->commit();
            }

            $record->markSucceeded($outcome);

            return $outcome;
        }

        if ($savepoint) {
            $this->database()->rollBack();
        }

        $errors = $outcome->errors();

        if ($step['errorKey'] !== null) {
            $context = $step['context'] instanceof Closure ? ($step['context'])($state, $key) : $step['context'];
            $errors[str_replace('{key}', (string) $key, $step['errorKey'])] = $outcome->withMergedContext($context)->message();
        }

        $record->markFailed($outcome, $failWith ?? $outcome->result, $errors);

        return $outcome;
    }

    /**
     * Build the outcome of a halted flow.
     *
     * @param  FlowState  $state
     * @return Outcome
     *
     * @throws LogicException When onFailure() does not return an Outcome.
     */
    private function failed(FlowState $state): Outcome
    {
        $outcome = $this->onFailure !== null
            ? ($this->onFailure)($state)
            : $this->defaultFailure($state);

        if (! $outcome instanceof Outcome) {
            throw new LogicException('Flow onFailure() must return an '.Outcome::class.'.');
        }

        return $outcome->withFlow($state);
    }

    /**
     * The flow's outcome class built from the failure's result, or else the failed step's
     * outcome with failWith's result; both with the flow's errors.
     *
     * @param  FlowState  $state
     * @return Outcome
     */
    private function defaultFailure(FlowState $state): Outcome
    {
        if ($this->outcome !== null) {
            return $this->outcome::fromResult($this->failureResult($state))->withErrors($state->errors());
        }

        $outcome = $state->failure()->outcome();
        $result = $state->failureResult();

        if ($result !== null && $result !== $outcome->result) {
            return $outcome::fromResult($result)->withErrors($state->errors());
        }

        return $outcome->withErrors(array_diff_key($state->errors(), $outcome->errors()));
    }

    /**
     * The result of the halting failure, or the flow's failWith (an exception outside any step).
     *
     * @param  FlowState  $state
     * @return ResultContract|null
     */
    private function failureResult(FlowState $state): ?ResultContract
    {
        return $state->failureResult() ?? $this->failWith;
    }

    /**
     * The connection running the transaction.
     *
     * @return ConnectionInterface
     */
    private function database(): ConnectionInterface
    {
        return DB::connection($this->connection);
    }
}
