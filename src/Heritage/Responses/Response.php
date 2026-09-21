<?php

declare(strict_types=1);

namespace Heritage\Responses;

use Heritage\Contracts\Result\ResultContract;
use Heritage\Http\Resources\Json\JsonResource;
use Heritage\Http\Resources\Json\ResourceCollection;
use Heritage\Support\Collection;

/**
 * Class Response
 *
 * Backward-compatible Response envelope alias extending the unified Outcome class.
 *
 * All operations can interchangeably use Outcome or Response without breaking existing code.
 */
class Response extends Outcome
{
    /**
     * Initialize Response instance by delegating to Outcome constructor.
     *
     * @param  ResultContract|null  $result  The domain result enum.
     * @param  JsonResource|ResourceCollection|Collection|array|null  $data  The returned payload data.
     * @param  array<string, mixed>  $context  Runtime context parameters.
     */
    public function __construct(
        ?ResultContract $result = null,
        JsonResource|ResourceCollection|Collection|array|null $data = null,
        array $context = [],
    ) {
        if ($result !== null) {
            parent::__construct(
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
        } else {
            parent::__construct(
                success: true,
                data: $data,
                context: $context
            );
        }
    }
}
