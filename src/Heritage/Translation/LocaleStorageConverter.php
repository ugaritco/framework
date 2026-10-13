<?php

declare(strict_types=1);

namespace Heritage\Translation;

use Heritage\Contracts\Translation\LocaleResolver;
use Heritage\Database\Connection;
use Heritage\Database\Schema\Blueprint;
use RuntimeException;

/**
 * Converts translation tables between the two locale storage modes:
 *  - "locale":    the locale code ("ar") in a string column (kernel ConfigLocaleResolver);
 *  - "locale_id": a foreign key to the locales table (i18n artifact's resolver).
 *
 * Values are mapped through the active LocaleResolver, which must be the i18n resolver
 * (storage "locale_id") in both directions: install i18n before converting to locale_id,
 * and convert back to locale before removing it.
 */
class LocaleStorageConverter
{
    public function __construct(
        protected Connection $connection,
        protected LocaleResolver $resolver,
    ) {}

    /**
     * Tables ending with "_translations" that have a "locale" or "locale_id" column.
     *
     * @return array<int, string>
     */
    public function translationTables(): array
    {
        $schema = $this->connection->getSchemaBuilder();

        return array_values(array_filter(
            array_map(fn (array $table) => $table['name'], $schema->getTables()),
            fn (string $table) => str_ends_with($table, '_translations')
                && ($schema->hasColumn($table, 'locale') || $schema->hasColumn($table, 'locale_id'))
        ));
    }

    /**
     * Plan (and unless $dryRun, perform) the conversion of one table.
     *
     * @param  'locale'|'locale_id'  $to
     * @return array{table: string, from: string, to: string, rows: int, mapping: array<string, string|int>, unmapped: array<int, string>, converted: bool, skipped: string|null}
     *
     * @throws RuntimeException When values cannot be mapped or the resolver cannot map them.
     */
    public function convert(string $table, string $to, bool $dryRun = false): array
    {
        if (! in_array($to, ['locale', 'locale_id'], true)) {
            throw new RuntimeException("Unknown locale storage [{$to}]; use \"locale\" or \"locale_id\".");
        }

        if ($this->resolver->storageColumn() !== 'locale_id' || $this->resolver->storageTable() === null) {
            throw new RuntimeException('Converting locale storage requires the i18n locale resolver (locale_id storage).');
        }

        $from = $to === 'locale_id' ? 'locale' : 'locale_id';
        $schema = $this->connection->getSchemaBuilder();
        $report = ['table' => $table, 'from' => $from, 'to' => $to, 'rows' => 0, 'mapping' => [], 'unmapped' => [], 'converted' => false, 'skipped' => null];

        if (! $schema->hasColumn($table, $from)) {
            $report['skipped'] = $schema->hasColumn($table, $to) ? "already uses {$to}" : "has no {$from} column";

            return $report;
        }

        $report['rows'] = $this->connection->table($table)->count();

        foreach ($this->connection->table($table)->distinct()->pluck($from) as $value) {
            $mapped = $to === 'locale_id'
                ? $this->resolver->toStorage((string) $value)
                : $this->resolver->fromStorage($value);

            $mapped === null
                ? $report['unmapped'][] = (string) $value
                : $report['mapping'][(string) $value] = $mapped;
        }

        if ($dryRun) {
            return $report;
        }

        if ($report['unmapped'] !== []) {
            throw new RuntimeException("Table [{$table}]: no locale for ".implode(', ', $report['unmapped']).'.');
        }

        $this->rebuild($table, $from, $to, $report['mapping']);
        $report['converted'] = true;

        return $report;
    }

    /**
     * Swap the locale column, carrying over its values, indexes and foreign key.
     *
     * @param  array<string, string|int>  $mapping
     */
    protected function rebuild(string $table, string $from, string $to, array $mapping): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $indexes = array_values(array_filter(
            $schema->getIndexes($table),
            fn (array $index) => ! $index['primary'] && in_array($from, $index['columns'], true)
        ));

        $foreignKeys = array_values(array_filter(
            $schema->getForeignKeys($table),
            fn (array $key) => in_array($from, $key['columns'], true)
        ));

        // 1. New nullable column, filled from the old one.
        $schema->table($table, function (Blueprint $blueprint) use ($to) {
            $to === 'locale_id'
                ? $blueprint->unsignedBigInteger('locale_id')->nullable()
                : $blueprint->string('locale', 10)->nullable();
        });

        foreach ($mapping as $old => $new) {
            $this->connection->table($table)->where($from, $old)->update([$to => $new]);
        }

        // 2. Drop what depends on the old column, then the column itself.
        $schema->table($table, function (Blueprint $blueprint) use ($foreignKeys, $indexes) {
            foreach ($foreignKeys as $key) {
                $blueprint->dropForeign($key['name'] ?? $key['columns']);
            }

            foreach ($indexes as $index) {
                $index['unique'] ? $blueprint->dropUnique($index['name']) : $blueprint->dropIndex($index['name']);
            }
        });

        $schema->table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($from));

        // 3. Required column, foreign key / index, and the former indexes on the new column.
        $schema->table($table, function (Blueprint $blueprint) use ($to, $indexes, $from) {
            if ($to === 'locale_id') {
                $blueprint->unsignedBigInteger('locale_id')->nullable(false)->change();
                $blueprint->foreign('locale_id')->references('id')->on($this->resolver->storageTable())->cascadeOnDelete();
            } else {
                $blueprint->string('locale', 10)->nullable(false)->change();
            }

            foreach ($indexes as $index) {
                $columns = array_map(fn ($column) => $column === $from ? $to : $column, $index['columns']);

                if ($index['unique']) {
                    $blueprint->unique($columns);
                } elseif ($columns !== [$to]) {
                    $blueprint->index($columns);
                }
            }

            // A code column gets its own index (a locale_id column is covered by its foreign key).
            if ($to === 'locale') {
                $blueprint->index('locale');
            }
        });
    }
}
