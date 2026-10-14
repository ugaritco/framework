<?php

namespace Heritage\Tests\Features;

use Closure;
use Error;
use Heritage\Capabilities\Capability;
use Heritage\Container\Container;
use Heritage\Contracts\Capabilities\CapabilityContract;
use Heritage\Contracts\Debug\ExceptionHandler;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Contracts\Result\ResultContract;
use Heritage\Database\Capsule\Manager as DB;
use Heritage\Database\Eloquent\ModelNotFoundException;
use Heritage\Database\Schema\Blueprint;
use Heritage\Enums\Actions\Feedback\Variant;
use Heritage\Features\Feature;
use Heritage\Features\Flow\Call;
use Heritage\Features\Flow\Flow;
use Heritage\Features\Flow\FlowState;
use Heritage\Features\Flow\FlowStatus;
use Heritage\Features\Flow\SkipReason;
use Heritage\Features\Flow\StepStatus;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;
use Heritage\Support\Facades\Facade;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use TypeError;

/**
 * The Flow execution contract: order, required / optional steps, needs, conditions,
 * each(), transactions and savepoints, exceptions, and the FlowState.
 */
class FlowTest extends TestCase
{
    protected DB $db;

    protected Container $container;

    /** @var array<int, Throwable> */
    public static array $reported = [];

    protected function setUp(): void
    {
        $this->db = new DB;
        $this->db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->setAsGlobal();

        $this->container = new Container;
        $this->container->instance('db', $this->db->getDatabaseManager());
        $this->container->instance(ExceptionHandler::class, new RecordingExceptionHandler);
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->container);

        $this->db->getConnection()->getSchemaBuilder()->create('rows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        static::$reported = [];
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
    }

    public function test_steps_run_in_order_and_expose_named_results(): void
    {
        $order = [];

        $outcome = (new Flow)
            ->step('first', $this->succeed('one', $order))
            ->step('second', $this->succeed('two', $order), needs: 'first')
            ->finish(fn (FlowState $state) => Outcome::success([$state->result('first'), $state->second]));

        $this->assertSame(['one', 'two'], $order);
        $this->assertSame(['one', 'two'], $outcome->data);
        $outcome->flow()->assertCompleted()->assertSucceeded('first')->assertSucceeded('second');
        $this->assertArrayNotHasKey('flow', $outcome->toArray());
    }

    public function test_a_required_failure_halts_rolls_back_and_leaves_later_steps_pending(): void
    {
        $finished = false;

        $outcome = (new Flow)
            ->step('insert', $this->insert('kept?'))
            ->step('fail', $this->failing(FlowResult::STEP_FAILED, ['field' => 'bad']), failWith: FlowResult::FLOW_FAILED)
            ->step('later', $this->succeed('later'))
            ->onFailure(fn (FlowState $state) => Outcome::fromResult($state->failureResult())->withErrors($state->errors()))
            ->finish(function () use (&$finished) {
                $finished = true;

                return Outcome::success();
            });

        $this->assertFalse($finished);
        $this->assertSame(0, $this->rows());
        $this->assertSame(FlowResult::FLOW_FAILED, $outcome->result);
        $this->assertSame(['field' => 'bad'], $outcome->errors());

        $outcome->flow()->assertHalted('fail')->assertSucceeded('insert')->assertFailed('fail')->assertPending('later');
    }

    public function test_without_on_failure_the_failed_step_outcome_is_returned_with_fail_with(): void
    {
        $outcome = (new Flow)
            ->step('fail', $this->failing(FlowResult::STEP_FAILED, ['field' => 'bad']), failWith: FlowResult::FLOW_FAILED)
            ->finish(fn () => Outcome::success());

        $this->assertSame(FlowResult::FLOW_FAILED, $outcome->result);
        $this->assertSame(['field' => 'bad'], $outcome->errors());

        $outcome = (new Flow)
            ->step('fail', $this->failing(FlowResult::STEP_FAILED))
            ->finish(fn () => Outcome::success());

        $this->assertSame(FlowResult::STEP_FAILED, $outcome->result);
    }

    public function test_an_optional_failure_rolls_back_its_own_changes_and_the_flow_continues(): void
    {
        $outcome = (new Flow)
            ->step('insert', $this->insert('kept'))
            ->step('optional', $this->insertThenFail('dropped'), optional: true)
            ->step('after', $this->succeed('after'))
            ->finish(fn (FlowState $state) => Outcome::success($state->resultOr('optional', 'none')));

        $this->assertSame('none', $outcome->data);
        $this->assertSame(['kept'], $this->names());
        $outcome->flow()->assertCompleted()->assertFailed('optional')->assertSucceeded('after');

        $this->expectException(LogicException::class);
        $outcome->flow()->result('optional');
    }

    public function test_needs_skip_a_step_whose_dependency_failed_or_was_skipped(): void
    {
        $outcome = (new Flow)
            ->step('a', $this->succeed('a'))
            ->step('notify', $this->failing(FlowResult::STEP_FAILED), optional: true)
            ->step('audit', $this->succeed('audit'), needs: 'notify')
            ->step('report', $this->succeed('report'), needs: ['a', 'audit'])
            ->step('both', $this->succeed('both'), needs: ['a'])
            ->finish(fn () => Outcome::success());

        $flow = $outcome->flow();
        $flow->assertSkipped('audit', SkipReason::DEPENDENCY_FAILED)
            ->assertSkipped('report', SkipReason::DEPENDENCY_SKIPPED)
            ->assertSucceeded('both')
            ->assertCompleted();
        $this->assertSame('notify', $flow->record('audit')->cause());
        $this->assertSame('audit', $flow->record('report')->cause());
    }

    public function test_needs_are_checked_before_the_condition(): void
    {
        $outcome = (new Flow)
            ->step('branch', $this->failing(FlowResult::STEP_FAILED), optional: true)
            ->step('cache', $this->succeed('cache'), needs: 'branch', when: fn (FlowState $state) => $state->result('branch') !== null)
            ->finish(fn () => Outcome::success());

        $outcome->flow()->assertSkipped('cache', SkipReason::DEPENDENCY_FAILED);
    }

    public function test_a_false_condition_skips_the_step(): void
    {
        $outcome = (new Flow)
            ->step('cache', $this->succeed('cache'), when: fn () => false)
            ->step('needsCache', $this->succeed('x'), needs: 'cache')
            ->finish(fn () => Outcome::success());

        $outcome->flow()
            ->assertSkipped('cache', SkipReason::CONDITION_NOT_MET)
            ->assertSkipped('needsCache', SkipReason::DEPENDENCY_SKIPPED);
    }

    public function test_an_exception_in_a_callback_halts_even_an_optional_step_and_is_reported(): void
    {
        $outcome = (new Flow)
            ->step('insert', $this->insert('rolled back'))
            ->step('optional', $this->succeed('x'), optional: true, when: fn () => throw new RuntimeException('bug'))
            ->onFailure(fn (FlowState $state) => Outcome::failure('failed'))
            ->finish(fn () => Outcome::success());

        $this->assertTrue($outcome->isFailure());
        $this->assertSame(0, $this->rows());
        $this->assertInstanceOf(RuntimeException::class, $outcome->flow()->exception());
        $outcome->flow()->assertHalted('optional')->assertFailed('optional');
        $this->assertCount(1, static::$reported);
    }

    public function test_an_exception_without_on_failure_is_rethrown_after_the_rollback(): void
    {
        try {
            (new Flow)
                ->step('insert', $this->insert('rolled back'))
                ->finish(fn () => throw new RuntimeException('finish failed'));

            $this->fail('The exception was not rethrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('finish failed', $e->getMessage());
        }

        $this->assertSame(0, $this->rows());
    }

    public function test_propagated_exceptions_roll_back_and_are_rethrown(): void
    {
        $called = false;

        try {
            (new Flow)
                ->step('insert', $this->insert('rolled back'))
                ->step('missing', new FlowCapability(fn () => throw new ModelNotFoundException))
                ->onFailure(function () use (&$called) {
                    $called = true;

                    return Outcome::failure();
                })
                ->finish(fn () => Outcome::success());

            $this->fail('The exception was not rethrown.');
        } catch (ModelNotFoundException) {
            $this->assertFalse($called);
        }

        $this->assertSame(0, $this->rows());
        $this->assertSame([], static::$reported);
    }

    public function test_each_records_items_and_halts_at_the_first_failed_required_item(): void
    {
        $outcome = (new Flow)
            ->step('branch', $this->succeed('main'))
            ->each('translations', ['ar' => 'ok', 'en' => 'bad', 'fr' => 'ok'],
                fn (string $value, string $locale) => Call::to($value === 'ok' ? $this->insert($locale) : $this->failing(FlowResult::STEP_FAILED))
                    ->failWith(FlowResult::FLOW_FAILED),
                errorKey: 'translations.{key}',
                context: fn (FlowState $state, string $locale) => ['locale' => strtoupper($locale), 'entity' => $state->result('branch')])
            ->finish(fn () => Outcome::success());

        $this->assertSame(FlowResult::FLOW_FAILED, $outcome->result);
        $this->assertSame(['translations.en' => 'Step failed for main in EN'], $outcome->errors());
        $this->assertSame(0, $this->rows());

        $items = $outcome->flow()->assertHalted('translations')->record('translations')->items();
        $this->assertSame([StepStatus::SUCCEEDED, StepStatus::FAILED, StepStatus::PENDING], array_map(fn ($item) => $item->status(), array_values($items)));
        $this->assertSame('en', $outcome->flow()->failure()->key);
    }

    public function test_an_optional_each_runs_every_item_and_fails_when_any_failed(): void
    {
        $outcome = (new Flow)
            ->each('items', ['a' => 'ok', 'b' => 'bad', 'c' => 'ok'],
                fn (string $value, string $key) => Call::to($value === 'ok' ? $this->insert($key) : $this->insertThenFail($key)),
                optional: true)
            ->step('after', $this->succeed('after'), needs: 'items')
            ->finish(fn () => Outcome::success());

        $this->assertSame(['a', 'c'], $this->names());
        $outcome->flow()->assertCompleted()->assertFailed('items')->assertSkipped('after', SkipReason::DEPENDENCY_FAILED);
    }

    public function test_an_each_without_items_succeeds_with_an_empty_result(): void
    {
        $outcome = (new Flow)
            ->each('items', [], fn () => Call::to($this->succeed('x')))
            ->finish(fn (FlowState $state) => Outcome::success($state->result('items')));

        $this->assertSame([], $outcome->data);
        $outcome->flow()->assertSucceeded('items');
    }

    public function test_definition_errors_are_raised_before_running(): void
    {
        try {
            (new Flow)->step('a', $this->succeed('a'))->step('a', $this->succeed('a'));
            $this->fail('A duplicated name was accepted.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        (new Flow)->step('a', $this->succeed('a'), needs: 'b');
    }

    public function test_class_string_targets_are_resolved_from_the_container(): void
    {
        $this->container->instance(FlowCapability::class, $this->succeed('resolved'));

        $outcome = (new Flow)
            ->step('resolved', FlowCapability::class)
            ->finish(fn (FlowState $state) => Outcome::success($state->result('resolved')));

        $this->assertSame('resolved', $outcome->data);
        $this->assertSame(FlowStatus::COMPLETED, $outcome->flow()->status());
    }

    public function test_without_on_failure_the_flow_outcome_is_built_from_the_failure(): void
    {
        $outcome = (new Flow(FlowOutcome::class, FlowResult::FLOW_FAILED))
            ->step('insert', $this->insert('rolled back'))
            ->step('fail', $this->failing(FlowResult::STEP_FAILED, ['field' => 'bad']))
            ->finish(fn () => Outcome::success());

        $this->assertInstanceOf(FlowOutcome::class, $outcome);
        $this->assertSame(FlowResult::FLOW_FAILED, $outcome->result);
        $this->assertSame(['field' => 'bad'], $outcome->errors());
        $this->assertSame(0, $this->rows());
        $outcome->flow()->assertHalted('fail');

        $outcome = (new Flow(FlowOutcome::class, FlowResult::FLOW_FAILED))
            ->step('fail', $this->failing(FlowResult::FLOW_FAILED), failWith: FlowResult::STEP_FAILED)
            ->finish(fn () => Outcome::success());

        $this->assertSame(FlowResult::STEP_FAILED, $outcome->result);
    }

    public function test_a_callback_exception_returns_the_flow_outcome_when_the_flow_has_a_fail_with(): void
    {
        $outcome = (new Flow(FlowOutcome::class, FlowResult::FLOW_FAILED))
            ->step('insert', $this->insert('rolled back'))
            ->finish(fn () => throw new RuntimeException('finish failed'));

        $this->assertInstanceOf(FlowOutcome::class, $outcome);
        $this->assertSame(FlowResult::FLOW_FAILED, $outcome->result);
        $this->assertSame(0, $this->rows());
        $this->assertCount(1, static::$reported);

        $this->expectException(RuntimeException::class);
        (new Flow(FlowOutcome::class))->finish(fn () => throw new RuntimeException('no result to build from'));
    }

    public function test_the_flow_outcome_must_be_an_outcome_class(): void
    {
        $this->expectException(LogicException::class);

        new Flow(FlowResult::class);
    }

    public function test_php_rejects_a_parcel_the_feature_does_not_accept(): void
    {
        $outcome = (new FlowFeature)->handle(FlowParcel::fromArray(['name' => 'ok']));

        $this->assertSame('ok', $outcome->data);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(FlowFeature::class.'::perform(): Argument #1 ($data) must be of type '.FlowParcel::class.', null given');
        (new FlowFeature)->handle(null);
    }

    public function test_a_capability_without_perform_is_a_programming_error(): void
    {
        $capability = new class extends Capability {
            protected function failed(Throwable $e): Outcome
            {
                return Outcome::failure();
            }
        };

        try {
            $capability->handle();
            $this->fail('A missing perform() was turned into an outcome.');
        } catch (Error $e) {
            $this->assertStringEndsWith('::perform()', $e->getMessage());
        }

        $this->expectException(Error::class);
        (new Flow(FlowOutcome::class, FlowResult::FLOW_FAILED))->step('missing', $capability)->finish(fn () => Outcome::success());
    }

    public function test_a_rejected_parcel_in_a_step_rolls_back_and_is_rethrown(): void
    {
        try {
            (new Flow(FlowOutcome::class, FlowResult::FLOW_FAILED))
                ->step('insert', $this->insert('rolled back'))
                ->step('feature', new FlowFeature)
                ->finish(fn () => Outcome::success());

            $this->fail('The TypeError was not rethrown.');
        } catch (TypeError) {
        }

        $this->assertSame(0, $this->rows());
        $this->assertSame([], static::$reported);
    }

    /**
     * A capability returning the given value as data.
     */
    protected function succeed(string $value, ?array &$order = null): FlowCapability
    {
        return new FlowCapability(function () use ($value, &$order) {
            $order[] = $value;

            return Outcome::success($value);
        });
    }

    /**
     * A capability returning a failed outcome.
     */
    protected function failing(FlowResult $result, array $errors = []): FlowCapability
    {
        return new FlowCapability(fn () => Outcome::fromResult($result)->withErrors($errors));
    }

    /**
     * A capability inserting a row.
     */
    protected function insert(string $name): FlowCapability
    {
        return new FlowCapability(function () use ($name) {
            $this->db->getConnection()->table('rows')->insert(['name' => $name]);

            return Outcome::success($name);
        });
    }

    /**
     * A capability inserting a row, then failing.
     */
    protected function insertThenFail(string $name): FlowCapability
    {
        return new FlowCapability(function () use ($name) {
            $this->db->getConnection()->table('rows')->insert(['name' => $name]);

            return Outcome::fromResult(FlowResult::STEP_FAILED);
        });
    }

    /**
     * The number of rows.
     */
    protected function rows(): int
    {
        return $this->db->getConnection()->table('rows')->count();
    }

    /**
     * The row names.
     *
     * @return array<int, string>
     */
    protected function names(): array
    {
        return $this->db->getConnection()->table('rows')->orderBy('id')->pluck('name')->all();
    }
}

class FlowCapability implements CapabilityContract
{
    public function __construct(protected Closure $callback)
    {
    }

    public function handle(Parcel|ParcelContract|null $data = null): Outcome
    {
        return ($this->callback)($data);
    }
}

readonly class FlowOutcome extends Outcome
{
}

class FlowParcel extends Parcel
{
    public function __construct(public string $name = '')
    {
    }
}

class FlowFeature extends Feature
{
    protected function perform(FlowParcel $data): Outcome
    {
        return $this->flow(FlowOutcome::class, FlowResult::FLOW_FAILED)
            ->finish(fn () => Outcome::success($data->name));
    }
}

enum FlowResult: int implements ResultContract
{
    case STEP_FAILED = 1;
    case FLOW_FAILED = 2;

    public function title(array $context = []): string
    {
        return $this->name;
    }

    public function message(array $context = []): string
    {
        return 'Step failed for '.($context['entity'] ?? '?').' in '.($context['locale'] ?? '?');
    }

    public function variant(): Variant
    {
        return Variant::ERROR;
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function isSuccess(): bool
    {
        return false;
    }
}

class RecordingExceptionHandler implements ExceptionHandler
{
    public function report(Throwable $e)
    {
        FlowTest::$reported[] = $e;
    }

    public function shouldReport(Throwable $e)
    {
        return true;
    }

    public function render($request, Throwable $e)
    {
        throw $e;
    }

    public function renderForConsole($output, Throwable $e)
    {
    }
}
