<?php

declare(strict_types=1);

namespace Heritage\Parcels;

/**
 * Class TranslationParcel
 *
 * Specialized data parcel representing entity translations across multiple supported locales.
 *
 * Architectural Characteristics:
 * - Inherits from the foundational Parcel class with customized null-filtering behavior.
 * - In database translation tables, users may intentionally clear or leave translation strings empty;
 *   therefore, shouldFilterNulls() returns false to ensure empty strings and nulls are preserved during save operations.
 */
abstract class TranslationParcel extends Parcel
{
    /**
     * Determine if null or uninitialized values should be filtered out in translation parcels.
     *
     * @return bool Always returns false to preserve empty strings and nulls in translation tables.
     */
    public function shouldFilterNulls(): bool
    {
        // Preserve empty or null translation fields during persistence and transformation
        return false;
    }
}
