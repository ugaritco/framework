<?php

declare(strict_types=1);

namespace Heritage\Repositories;

use Heritage\Contracts\Repositories\TranslationRepositoryInterface;
use Heritage\Database\Eloquent\Builder;
use Heritage\Database\Eloquent\ModelTranslation;
use Heritage\Database\Eloquent\SoftDeletes;
use Heritage\Parcels\Parcel;
use Heritage\Translation\Locales;
use InvalidArgumentException;

/**
 * Class TranslationRepository
 *
 * Base repository of a translation model (a ModelTranslation such as BranchTranslation).
 *
 * Upper layers speak in locale codes ("ar", "en"); the repository converts them through the
 * LocaleResolver to whatever the translation table stores (the code, or a locale_id).
 * Saving is an upsert on (parent, locale): one translation row per locale.
 *
 * @template TModel of ModelTranslation
 *
 * @extends ModelRepository<TModel>
 *
 * @implements TranslationRepositoryInterface<TModel>
 */
abstract class TranslationRepository extends ModelRepository implements TranslationRepositoryInterface
{
    /**
     * Create or update a translation from a parcel holding the parent key, a "locale" code
     * and the translated attributes.
     *
     * A Parcel with an attached translation updates it; otherwise the translation of the
     * parent and locale is updated when it exists (restored when soft deleted), or created.
     *
     * @param  Parcel  $parcel  Parent key, locale code and translated attributes.
     * @return TModel The persisted translation, freshly loaded.
     *
     * @throws InvalidArgumentException When the locale is not accepted or the parent key is missing.
     */
    public function save(Parcel $parcel): ModelTranslation
    {
        $data = $parcel->toAttributes();

        $locale = $data['locale'] ?? null;
        unset($data['locale'], $data['id']);

        $translation = $parcel->getModel();

        if (! $translation) {
            $parentId = $data[$this->foreignKey()] ?? null;

            if ($parentId === null || $locale === null) {
                throw new InvalidArgumentException('A new translation needs its parent key ['.$this->foreignKey().'] and a locale code.');
            }

            $translation = $this->queryForLocale($parentId, (string) $locale)
                ->when($this->usesSoftDeletes(), fn (Builder $query) => $query->withTrashed())
                ->first()
                ?? $this->model->newInstance()->setAttribute($this->localeColumn(), $this->toStorage((string) $locale));

            if ($this->usesSoftDeletes() && $translation->trashed()) {
                $translation->setAttribute($translation->getDeletedAtColumn(), null);
            }
        }

        $translation->fill($data)->save();

        return $translation->fresh();
    }

    /**
     * Find the translation of a parent record for a locale code.
     *
     * @param  int|string  $parentId  Primary key of the parent record.
     * @param  string  $locale  Locale code ("ar", "en").
     * @return TModel|null The translation, or null when the locale has none.
     */
    public function findForLocale(int|string $parentId, string $locale): ?ModelTranslation
    {
        return $this->queryForLocale($parentId, $locale)->first();
    }

    /**
     * Delete the translation of a parent record for a locale code.
     *
     * @param  int|string  $parentId  Primary key of the parent record.
     * @param  string  $locale  Locale code ("ar", "en").
     * @return bool True when a translation was deleted, false when the locale had none.
     */
    public function destroyForLocale(int|string $parentId, string $locale): bool
    {
        $translation = $this->findForLocale($parentId, $locale);

        return $translation !== null && (bool) $translation->delete();
    }

    /**
     * Query the translation of a parent record for a locale code.
     *
     * @param  int|string  $parentId  Primary key of the parent record.
     * @param  string  $locale  Locale code ("ar", "en").
     * @return Builder
     */
    protected function queryForLocale(int|string $parentId, string $locale): Builder
    {
        return $this->query()
            ->where($this->foreignKey(), $parentId)
            ->where($this->localeColumn(), $this->toStorage($locale));
    }

    /**
     * The stored value of a locale code.
     *
     * @param  string  $locale  Locale code ("ar", "en").
     * @return string|int The code or the locale id, as the resolver stores it.
     *
     * @throws InvalidArgumentException When the locale is not accepted.
     */
    protected function toStorage(string $locale): string|int
    {
        return Locales::resolver()->toStorage($locale)
            ?? throw new InvalidArgumentException("Locale [{$locale}] is not available for translations.");
    }

    /**
     * The column referencing the parent record.
     *
     * @return string
     */
    protected function foreignKey(): string
    {
        return $this->translationModel()->getParentForeignKey();
    }

    /**
     * The column storing the locale.
     *
     * @return string
     */
    protected function localeColumn(): string
    {
        return $this->translationModel()->getLocaleColumn();
    }

    /**
     * Whether the translation model soft deletes its rows.
     *
     * @return bool
     */
    protected function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model), true);
    }

    /**
     * The translation model this repository is bound to.
     *
     * @return TModel
     *
     * @throws InvalidArgumentException When the repository is bound to another model.
     */
    protected function translationModel(): ModelTranslation
    {
        if (! $this->model instanceof ModelTranslation) {
            throw new InvalidArgumentException(static::class.' must be bound to a '.ModelTranslation::class.'.');
        }

        return $this->model;
    }
}
