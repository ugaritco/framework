<?php

declare(strict_types=1);

namespace Heritage\Contracts\Repositories;

use Heritage\Database\Eloquent\ModelTranslation;
use Heritage\Parcels\Parcel;

/**
 * Interface TranslationRepositoryInterface
 *
 * Contract for the repository of a translation model, addressed by locale code.
 *
 * @template TModel of ModelTranslation
 *
 * @extends ModelRepositoryInterface<TModel>
 */
interface TranslationRepositoryInterface extends ModelRepositoryInterface
{
    /**
     * Create or update a translation; a Parcel without an attached model is an upsert on its
     * parent key and "locale" code.
     *
     * @param  Parcel  $parcel  Parent key, locale code and translated attributes.
     * @return TModel The persisted translation, freshly loaded.
     */
    public function save(Parcel $parcel): ModelTranslation;

    /**
     * Find the translation of a parent record for a locale code.
     *
     * @param  int|string  $parentId  Primary key of the parent record.
     * @param  string  $locale  Locale code ("ar", "en").
     * @return TModel|null The translation, or null when the locale has none.
     */
    public function findForLocale(int|string $parentId, string $locale): ?ModelTranslation;

    /**
     * Delete the translation of a parent record for a locale code.
     *
     * @param  int|string  $parentId  Primary key of the parent record.
     * @param  string  $locale  Locale code ("ar", "en").
     * @return bool True when a translation was deleted, false when the locale had none.
     */
    public function destroyForLocale(int|string $parentId, string $locale): bool;
}
