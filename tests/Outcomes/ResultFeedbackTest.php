<?php

namespace Heritage\Tests\Outcomes;

use Heritage\Container\Container;
use Heritage\Contracts\Result\ResultContract;
use Heritage\Enums\Actions\Feedback\Variant;
use Heritage\Enums\Concerns\ResolvesResultFeedback;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Outcomes\Outcome;
use Heritage\Translation\ArrayLoader;
use Heritage\Translation\Translator;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Result feedback derived from case names, and Outcome copies that keep their subclass.
 */
class ResultFeedbackTest extends TestCase
{
    protected Container $previousContainer;

    /**
     * Bind a translator holding a few `responses.*` lines.
     */
    protected function setUp(): void
    {
        $this->previousContainer = Container::getInstance();

        $loader = new ArrayLoader;
        $loader->addMessages('en', 'responses', [
            'saved' => ['title' => 'Saved', 'message' => ':model saved'],
            'failed_saved' => ['title' => 'Not saved', 'message' => ':model not saved'],
        ]);

        $container = new Container;
        Container::setInstance($container);
        $container->instance('translator', new Translator($loader, 'en'));
    }

    /**
     * Restore the global container.
     */
    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
    }

    /**
     * Call the enum's protected feedbackKey().
     */
    protected function key(ResultContract $result): string
    {
        return (fn () => $this->feedbackKey())->call($result);
    }

    public function test_translation_key_is_derived_from_the_case_name(): void
    {
        $this->assertSame('saved', $this->key(SaveResult::SUCCESS));
        $this->assertSame('saved_no_translation', $this->key(SaveResult::SUCCESS_NO_TRANSLATION));
        $this->assertSame('failed_saved', $this->key(SaveResult::FAILED));
        $this->assertSame('failed_model_save', $this->key(SaveResult::FAILED_MODEL_SAVE));

        $this->assertSame('translation.saved', $this->key(TranslationResult::SUCCESS));
        $this->assertSame('translation.failed_saved', $this->key(TranslationResult::FAILED));
    }

    public function test_success_variant_and_status_follow_the_case_prefix(): void
    {
        $this->assertTrue(SaveResult::SUCCESS_NO_TRANSLATION->isSuccess());
        $this->assertFalse(SaveResult::FAILED_MODEL_SAVE->isSuccess());
        $this->assertSame(Variant::SUCCESS, SaveResult::SUCCESS->variant());
        $this->assertSame(Variant::ERROR, SaveResult::FAILED->variant());
        $this->assertSame(200, SaveResult::SUCCESS->statusCode());
        $this->assertSame(400, SaveResult::FAILED_MODEL_SAVE->statusCode());
    }

    public function test_title_and_message_are_translated_with_the_context(): void
    {
        $this->assertSame('Saved', SaveResult::SUCCESS->title());
        $this->assertSame('Branch not saved', SaveResult::FAILED->message(['model' => 'Branch']));
    }

    public function test_an_enum_overrides_some_cases_and_keeps_the_convention_for_the_others(): void
    {
        $this->assertSame('failed', $this->key(OverriddenResult::FAILED));
        $this->assertSame('saved', $this->key(OverriddenResult::SUCCESS));
        $this->assertSame(Variant::INFO, OverriddenResult::SUCCESS_PARTIAL->variant());
        $this->assertSame(Variant::SUCCESS, OverriddenResult::SUCCESS->variant());
    }

    public function test_an_enum_without_feedback_constant_is_a_logic_error(): void
    {
        $this->expectException(LogicException::class);

        $this->key(UnconfiguredResult::SUCCESS);
    }

    public function test_with_errors_copies_a_subclass_with_its_own_constructor(): void
    {
        $outcome = new SubjectOutcome(SaveResult::FAILED, 'branch');

        $copy = $outcome->withErrors(['slug' => 'Reserved.'])->withErrors(['name' => 'Taken.']);

        $this->assertInstanceOf(SubjectOutcome::class, $copy);
        $this->assertSame('branch', $copy->subject);
        $this->assertSame(SaveResult::FAILED, $copy->result);
        $this->assertSame(['slug' => 'Reserved.', 'name' => 'Taken.'], $copy->errors());
        $this->assertSame(['subject' => 'branch'], $copy->getContext());
        $this->assertSame([], $outcome->errors());
    }

    public function test_with_merged_context_copies_a_subclass(): void
    {
        $copy = (new SubjectOutcome(SaveResult::SUCCESS, 'branch'))->withMergedContext(['model' => 'Branch']);

        $this->assertInstanceOf(SubjectOutcome::class, $copy);
        $this->assertSame(['subject' => 'branch', 'model' => 'Branch'], $copy->getContext());
    }

    public function test_feedback_is_read_from_the_result_with_the_current_context(): void
    {
        $outcome = Outcome::fromResult(SaveResult::FAILED, null, ['model' => 'Branch']);

        $this->assertSame('Branch not saved', $outcome->message());
        $this->assertSame('Sector not saved', $outcome->withMergedContext(['model' => 'Sector'])->message());
        $this->assertSame('Not saved', $outcome->title());
        $this->assertSame(Variant::ERROR, $outcome->variant());
        $this->assertSame(400, $outcome->statusCode());
    }

    public function test_default_context_is_merged_under_the_given_context(): void
    {
        $this->assertSame('Branch saved', DefaultContextOutcome::fromResult(SaveResult::SUCCESS)->message());
        $this->assertSame('Sector saved', DefaultContextOutcome::fromResult(SaveResult::SUCCESS, null, ['model' => 'Sector'])->message());
    }

    public function test_with_data_presents_the_payload_and_subject_unwraps_it(): void
    {
        $model = (object) ['slug' => 'central'];
        $outcome = Outcome::fromResult(SaveResult::SUCCESS, $model);
        $presented = $outcome->withData(new JsonResource($model));

        $this->assertInstanceOf(JsonResource::class, $presented->data);
        $this->assertSame($model, $presented->subject());
        $this->assertSame($model, $outcome->data);
        $this->assertSame(SaveResult::SUCCESS, $presented->result);
    }
}

readonly class DefaultContextOutcome extends Outcome
{
    protected function defaultContext(): array
    {
        return ['model' => 'Branch'];
    }
}

enum SaveResult: int implements ResultContract
{
    use ResolvesResultFeedback;

    const FEEDBACK = 'saved';

    case SUCCESS = 1;
    case SUCCESS_NO_TRANSLATION = 2;
    case FAILED = 3;
    case FAILED_MODEL_SAVE = 4;
}

enum TranslationResult: int implements ResultContract
{
    use ResolvesResultFeedback;

    const FEEDBACK = 'translation.saved';

    case SUCCESS = 1;
    case FAILED = 2;
}

enum OverriddenResult: int implements ResultContract
{
    use ResolvesResultFeedback;

    const FEEDBACK = 'saved';

    case SUCCESS = 1;
    case SUCCESS_PARTIAL = 2;
    case FAILED = 3;

    protected function feedbackKey(): string
    {
        return match ($this) {
            self::FAILED => 'failed',
            default => $this->conventionalFeedbackKey(),
        };
    }

    public function variant(): Variant
    {
        return match ($this) {
            self::SUCCESS_PARTIAL => Variant::INFO,
            default => $this->conventionalVariant(),
        };
    }
}

enum UnconfiguredResult: int implements ResultContract
{
    use ResolvesResultFeedback;

    case SUCCESS = 1;
}

readonly class SubjectOutcome extends Outcome
{
    public string $subject;

    public function __construct(ResultContract $result, string $subject)
    {
        $this->subject = $subject;

        parent::__construct(success: $result->isSuccess(), result: $result, context: ['subject' => $subject]);
    }
}
