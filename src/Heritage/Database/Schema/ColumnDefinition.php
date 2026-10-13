<?php

namespace Heritage\Database\Schema;

use Heritage\Support\Fluent;

/**
 * @method $this after(string $column) Place the column "after" another column (MySQL)
 * @method $this always(bool $value = true) Used as a modifier for generatedAs() (PostgreSQL)
 * @method $this autoIncrement() Set INTEGER columns as auto-increment (primary key)
 * @method $this change() Change the column
 * @method $this charset(string $charset) Specify a character set for the column (MySQL)
 * @method $this collation(string $collation) Specify a collation for the column
 * @method $this comment(string $comment) Add a comment to the column (MySQL/PostgreSQL)
 * @method $this default(mixed $value) Specify a "default" value for the column
 * @method $this first() Place the column "first" in the table (MySQL)
 * @method $this from(int $startingValue) Set the starting value of an auto-incrementing field (MySQL/PostgreSQL)
 * @method $this fulltext(bool|string $indexName = null) Add a fulltext index
 * @method $this generatedAs(string|\Heritage\Contracts\Database\Query\Expression $expression = null) Create a SQL compliant identity column (PostgreSQL)
 * @method $this instant() Specify that algorithm=instant should be used for the column operation (MySQL)
 * @method $this index(bool|string $indexName = null) Add an index
 * @method $this invisible() Specify that the column should be invisible to "SELECT *" (MySQL)
 * @method $this lock(('none'|'shared'|'default'|'exclusive') $value) Specify the DDL lock mode for the column operation (MySQL)
 * @method $this nullable(bool $value = true) Allow NULL values to be inserted into the column
 * @method $this persisted() Mark the computed generated column as persistent (SQL Server)
 * @method $this primary(bool $value = true) Add a primary index
 * @method $this spatialIndex(bool|string $indexName = null) Add a spatial index
 * @method $this vectorIndex(bool|string $indexName = null) Add a vector index
 * @method $this startingValue(int $startingValue) Set the starting value of an auto-incrementing field (MySQL/PostgreSQL)
 * @method $this storedAs(string|\Heritage\Contracts\Database\Query\Expression $expression) Create a stored generated column (MySQL/PostgreSQL/SQLite)
 * @method $this type(string $type) Specify a type for the column
 * @method $this unique(bool|string $indexName = null) Add a unique index
 * @method $this unsigned(bool $value = true) Set the INTEGER column as UNSIGNED if value is true (MySQL)
 * @method $this using(string|\Heritage\Contracts\Database\Query\Expression $expression) Specify a casting expression when changing the column type (PostgreSQL)
 * @method $this useCurrent() Set the TIMESTAMP column to use CURRENT_TIMESTAMP as default value
 * @method $this useCurrentOnUpdate() Set the TIMESTAMP column to use CURRENT_TIMESTAMP when updating (MySQL)
 * @method $this translation(bool $value = true) Mark the column as translatable for the translation table
 * @method $this translatable(bool $value = true) Mark the column as translatable for the translation table
 * @method $this translate(bool $value = true) Mark the column as translatable for the translation table
 * @method $this withTranslations(bool $value = true) Keep the column and add it to the translation table too
 * @method $this virtualAs(string|\Heritage\Contracts\Database\Query\Expression $expression) Create a virtual generated column (MySQL/PostgreSQL/SQLite)
 */
class ColumnDefinition extends Fluent
{
    /**
     * Mark the column as translatable: createTranslatable() moves it to the translation table.
     *
     * @param  bool  $value
     * @return $this
     */
    public function translatable(bool $value = true)
    {
        $this->attributes['translatable'] = $value;

        return $this;
    }

    /**
     * Determine if the column is marked as translatable.
     *
     * @return bool
     */
    public function isTranslatable(): bool
    {
        return (bool) $this->get('translatable');
    }

    /**
     * Keep the column on the main table and add it to the translation table too
     * (createTranslatable()); softDeletes()->withTranslations() soft deletes translation rows.
     *
     * @param  bool  $value
     * @return $this
     */
    public function withTranslations(bool $value = true)
    {
        $this->attributes['withTranslations'] = $value;

        return $this;
    }

    /**
     * Determine if the column is also added to the translation table.
     *
     * @return bool
     */
    public function isWithTranslations(): bool
    {
        return (bool) $this->get('withTranslations');
    }

    /**
     * Mark the column as the table's soft delete column (set by Blueprint::softDeletes()).
     *
     * @return $this
     */
    public function markAsSoftDeletes()
    {
        $this->attributes['softDeletes'] = true;

        return $this;
    }

    /**
     * Determine if the column is the table's soft delete column.
     *
     * @return bool
     */
    public function isSoftDeletes(): bool
    {
        return (bool) $this->get('softDeletes');
    }
}

