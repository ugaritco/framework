<?php

declare(strict_types=1);

namespace Heritage\DTOs;

/**
 * Class TranslationDTO
 *
 * Specialized Data Transfer Object for entity translations across multiple locales.
 *
 * Architectural Characteristics:
 * - Inherits from the base DTO class with customized null-filtering behavior.
 * - In database translation tables, users may intentionally clear or leave translation strings empty;
 *   therefore, shouldFilterNulls() returns false to ensure empty strings and nulls are preserved during save operations.
 */
abstract class TranslationDTO extends DTO
{
    /**
     * Determine if null values should be filtered out in translation DTOs.
     *
     * @return bool Always returns false to preserve empty strings and nulls in translation tables.
     */
    public function shouldFilterNulls(): bool
    {
        // Return false to allow empty or null translation values to persist
        return false;
    }
}
