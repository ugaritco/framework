<?php

declare(strict_types=1);

namespace Heritage\Contracts\Features;

use Heritage\DTOs\DTO;
use Heritage\Responses\Outcome;

/**
 * Interface FeatureContract
 *
 * Contract for broad application features that coordinate across multiple UseCases, Services,
 * or modules to achieve a comprehensive business capability.
 */
interface FeatureContract
{
    /**
     * Execute the feature orchestration workflow.
     *
     * @param  ?DTO  $data  Input Data Transfer Object.
     * @return Outcome The unified execution outcome envelope.
     */
    public function handle(?DTO $data): Outcome;
}
