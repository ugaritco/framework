<?php

declare(strict_types=1);

namespace Heritage\Features;

use Heritage\Contracts\Features\FeatureContract;
use Heritage\DTOs\DTO;
use Heritage\Responses\Outcome;
use Heritage\Traits\Concerns\HasTransaction;

/**
 * Class Feature
 *
 * Base abstract class for application Features.
 *
 * Architectural Role & Hierarchy:
 * - Positioned at the top of the domain workflow hierarchy (Feature -> Services -> UseCases).
 * - Responsible for executing comprehensive business capabilities that coordinate across MULTIPLE UseCases
 *   and/or MULTIPLE Services spanning different modules or artifacts.
 * - Example: An OnboardUserFeature may coordinate SaveUserService (user + translations in i18n module),
 *   AssignUserGeographyService (geography module), and DispatchWelcomeNotificationUseCase (notifications).
 * - Uses the HasTransaction trait to provide cross-domain database transaction management when needed.
 *
 * @template TInput of DTO
 */
abstract class Feature implements FeatureContract
{
    use HasTransaction;

    /**
     * Execute the high-level feature workflow coordinating across multiple UseCases and Services.
     *
     * @param  ?DTO  $data  Input Data Transfer Object.
     * @return Outcome The unified outcome envelope of the feature execution.
     */
    abstract public function handle(?DTO $data): Outcome;
}
