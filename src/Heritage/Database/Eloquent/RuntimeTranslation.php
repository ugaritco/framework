<?php

declare(strict_types=1);

namespace Heritage\Database\Eloquent;

/**
 * Translation model used when a translatable model has no translation class of its own.
 *
 * One real class serves every model: each instance is configured with its table, parent
 * model and parent foreign key, and that configuration is carried to every instance Eloquent
 * derives from it (query results, relations), so no class has to be generated at runtime.
 */
class RuntimeTranslation extends ModelTranslation
{
    /**
     * The translatable model class this translation belongs to.
     *
     * @var class-string<Model>|null
     */
    protected ?string $parentModel = null;

    /**
     * The foreign key referencing the parent model.
     */
    protected ?string $parentForeignKey = null;

    /**
     * Create a translation instance configured for a translatable model.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function for(Model $parent, array $attributes = []): static
    {
        $instance = new static;
        $instance->parentModel = $parent::class;
        $instance->parentForeignKey = $parent->getTranslationForeignKey();
        $instance->setTable($parent->getTranslationTable());
        $instance->setConnection($parent->getConnectionName());

        return $instance->fill($attributes);
    }

    /**
     * Carry the table and parent configuration to derived instances.
     *
     * @param  array<string, mixed>  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $instance = parent::newInstance([], $exists);
        $instance->parentModel = $this->parentModel;
        $instance->parentForeignKey = $this->parentForeignKey;

        return $instance->fill((array) $attributes);
    }
}
