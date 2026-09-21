<?php

declare(strict_types=1);

namespace Heritage\Contracts\Result;

use Heritage\Enums\Actions\Feedback\Variant;

/**
 * Interface ResultContract
 *
 * Mandatory contract for all domain result enums and status objects.
 *
 * Architectural Importance:
 * - Every UseCase or operation maps to a Result enum implementing this contract.
 * - Bridges the logical business state (Business Result) with technical HTTP transport (statusCode)
 *   and presentation layers (title, message, visual variant).
 */
interface ResultContract
{
    /**
     * Get the user-facing feedback title for the operation with context substitutions.
     *
     * @param  array<string, mixed>  $context  Runtime context array for dynamic title rendering.
     * @return string
     */
    public function title(array $context = []): string;

    /**
     * Get the user-facing feedback message for the operation with context substitutions.
     *
     * @param  array<string, mixed>  $context  Runtime context array for dynamic message rendering.
     * @return string
     */
    public function message(array $context = []): string;

    /**
     * Get the visual feedback variant (SUCCESS, ERROR, WARNING, INFO).
     *
     * @return Variant
     */
    public function variant(): Variant;

    /**
     * Get the standard numeric HTTP status code associated with this result (e.g. 200, 201, 400, 404, 422).
     *
     * @return int
     */
    public function statusCode(): int;

    /**
     * Determine whether this result represents a successful operation.
     *
     * @return bool
     */
    public function isSuccess(): bool;
}
