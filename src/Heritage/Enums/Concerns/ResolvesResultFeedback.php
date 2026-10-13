<?php

declare(strict_types=1);

namespace Heritage\Enums\Concerns;

use Heritage\Enums\Actions\Feedback\Variant;
use LogicException;

/**
 * Trait ResolvesResultFeedback
 *
 * Implements ResultContract for a Result enum from its case names and a FEEDBACK constant
 * holding the base translation key under `responses.` (e.g. 'saved', 'translation.saved'):
 *
 * | Case        | Translation key                 | Success | Variant | Status |
 * | :---------- | :------------------------------ | :------ | :------ | :----- |
 * | SUCCESS     | responses.saved                 | yes     | SUCCESS | 200    |
 * | SUCCESS_X   | responses.saved_x               | yes     | SUCCESS | 200    |
 * | FAILED      | responses.failed_saved          | no      | ERROR   | 400    |
 * | FAILED_X    | responses.failed_x              | no      | ERROR   | 400    |
 *
 * The title and message are `{key}.title` and `{key}.message`. To depart from the convention
 * for some cases, override feedbackKey(), feedbackContext(), variant() or statusCode() in the
 * enum and fall back to the conventional*() method for the other cases:
 *
 *     protected function feedbackKey(): string
 *     {
 *         return match ($this) {
 *             self::FAILED => 'failed',
 *             default => $this->conventionalFeedbackKey(),
 *         };
 *     }
 *
 * @mixin \UnitEnum
 */
trait ResolvesResultFeedback
{
    /**
     * Localized title of the result.
     *
     * @param  array<string, mixed>  $context  Placeholder values.
     * @return string
     */
    public function title(array $context = []): string
    {
        return trans('responses.'.$this->feedbackKey().'.title', $this->feedbackContext($context));
    }

    /**
     * Localized message of the result.
     *
     * @param  array<string, mixed>  $context  Placeholder values.
     * @return string
     */
    public function message(array $context = []): string
    {
        return trans('responses.'.$this->feedbackKey().'.message', $this->feedbackContext($context));
    }

    /**
     * UI feedback variant of the result.
     *
     * @return Variant
     */
    public function variant(): Variant
    {
        return $this->conventionalVariant();
    }

    /**
     * HTTP status code of the result.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->conventionalStatusCode();
    }

    /**
     * Whether the result is a success: SUCCESS and SUCCESS_* cases.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->name === 'SUCCESS' || str_starts_with($this->name, 'SUCCESS_');
    }

    /**
     * The translation key of the result, relative to `responses.`.
     *
     * @return string
     */
    protected function feedbackKey(): string
    {
        return $this->conventionalFeedbackKey();
    }

    /**
     * The placeholder values passed to the translator; override to add case-specific values.
     *
     * @param  array<string, mixed>  $context  Placeholder values given by the outcome.
     * @return array<string, mixed>
     */
    protected function feedbackContext(array $context): array
    {
        return $context;
    }

    /**
     * The translation key derived from the case name and the FEEDBACK constant.
     *
     * @return string
     *
     * @throws LogicException When the enum does not define FEEDBACK.
     */
    protected function conventionalFeedbackKey(): string
    {
        if (! defined(static::class.'::FEEDBACK')) {
            throw new LogicException(static::class.' must define a FEEDBACK constant holding its base translation key.');
        }

        $base = constant(static::class.'::FEEDBACK');
        $dot = strrpos($base, '.');
        $group = $dot === false ? '' : substr($base, 0, $dot + 1);
        $action = $dot === false ? $base : substr($base, $dot + 1);
        $case = strtolower($this->name);

        return $group.match (true) {
            $case === 'success' => $action,
            $case === 'failed' => 'failed_'.$action,
            str_starts_with($case, 'success_') => $action.substr($case, strlen('success')),
            default => $case,
        };
    }

    /**
     * SUCCESS for a successful result, ERROR otherwise.
     *
     * @return Variant
     */
    protected function conventionalVariant(): Variant
    {
        return $this->isSuccess() ? Variant::SUCCESS : Variant::ERROR;
    }

    /**
     * 200 for a successful result, 400 otherwise.
     *
     * @return int
     */
    protected function conventionalStatusCode(): int
    {
        return $this->isSuccess() ? 200 : 400;
    }
}
