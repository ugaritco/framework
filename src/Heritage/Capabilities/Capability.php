<?php

declare(strict_types=1);

namespace Heritage\Capabilities;

use Heritage\Auth\Access\AuthorizationException;
use Heritage\Contracts\Capabilities\CapabilityContract;
use Heritage\Contracts\Parcels\ParcelContract;
use Heritage\Database\Eloquent\ModelNotFoundException;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;
use Heritage\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Error;
use Throwable;
use TypeError;

/**
 * Class Capability
 *
 * Base abstract class for execution capabilities in the Ugarit Ecosystem.
 *
 * Architectural Role & Hierarchy:
 * - A Capability represents an execution unit that carries out a single, clearly defined operation.
 * - It can interact with one or multiple models, at the module/artifact or application level.
 * - Encapsulates atomic persistence, mutation, query, or computational logic.
 * - Coordinates with repositories via dependency inversion: repository contracts are
 *   injected into the constructor and resolved by the container, which maps an unbound
 *   contract to its repository by convention (Contracts\...\XRepositoryInterface ->
 *   Repositories\...\XRepository).
 *
 * A capability implements two methods:
 *
 *     protected function perform(SaveBranchParcel $data): Outcome   // the operation
 *     protected function failed(Throwable $e): Outcome               // the failure outcome
 *
 * The type of perform()'s parameter declares the accepted Parcel (a nullable type,
 * ?IndexBranchParcel, also accepts no input); PHP enforces it, and the TypeError of an input
 * perform() does not accept is rethrown as it is. handle() runs perform() and turns any other
 * exception into failed() after reporting it; exceptions listed in $propagate (HTTP,
 * authorization, validation, not found) are rethrown for the framework to render.
 *
 * An expected domain failure is thrown as a CapabilityException: it is not reported, and
 * its displayable errors are added to the failed() outcome's `errors`.
 *
 * @method Outcome perform(mixed $data = null) Execute the operation.
 */
abstract class Capability implements CapabilityContract
{
    /**
     * Exceptions rethrown by handle() instead of becoming a failed outcome.
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
     * Execute the capability: run perform() and turn an exception into the failed() outcome.
     *
     * @param  Parcel|ParcelContract|null  $data  Input Parcel (or null for parameterless operations).
     * @return Outcome Structured outcome envelope containing result status and payload.
     *
     * @throws TypeError When perform() does not accept the input.
     * @throws Error When the capability does not define perform().
     */
    final public function handle(Parcel|ParcelContract|null $data = null): Outcome
    {
        try {
            return $this->perform($data);
        } catch (Throwable $e) {
            if (static::isMisuse($e)) {
                throw $e;
            }

            foreach ($this->propagate as $class) {
                if ($e instanceof $class) {
                    throw $e;
                }
            }

            // An expected domain failure carries displayable reasons and is not a bug to report
            if ($e instanceof CapabilityException) {
                return $this->failed($e)->withErrors($e->errors());
            }

            if (function_exists('report')) {
                report($e);
            }

            return $this->failed($e);
        }
    }

    /**
     * Build the outcome returned when perform() throws. Unexpected exceptions are already
     * reported and their details never reach the outcome; a CapabilityException's errors
     * are merged into the returned outcome.
     *
     * @param  Throwable  $e  The exception raised by perform().
     * @return Outcome The capability's failure outcome.
     */
    abstract protected function failed(Throwable $e): Outcome;

    /**
     * Whether the exception is a misuse of perform() (of a capability or a feature), a
     * programming error rather than one raised inside perform(): PHP's TypeError for the input
     * given to perform() itself, or the Error of a class that does not define perform().
     *
     * @param  Throwable  $e
     * @return bool
     */
    public static function isMisuse(Throwable $e): bool
    {
        if ($e instanceof TypeError) {
            $frame = $e->getTrace()[0] ?? [];

            return ($frame['function'] ?? null) === 'perform'
                && str_starts_with($e->getMessage(), ($frame['class'] ?? '').'::perform(): Argument #1 ');
        }

        return $e instanceof Error
            && preg_match('/^Call to undefined method [^:]+::perform\(\)$/', $e->getMessage()) === 1;
    }
}
