<?php

namespace Heritage\Database\Schema;

use Closure;
use Heritage\Container\Container;
use Heritage\Database\Connection;
use Heritage\Database\PostgresConnection;
use Heritage\Support\Str;
use Heritage\Support\Traits\Macroable;
use Heritage\Translation\Locales;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class Builder
{
    use Macroable;

    /**
     * The database connection instance.
     *
     * @var \Heritage\Database\Connection
     */
    protected $connection;

    /**
     * The schema grammar instance.
     *
     * @var \Heritage\Database\Schema\Grammars\Grammar
     */
    protected $grammar;

    /**
     * The Blueprint resolver callback.
     *
     * @var \Closure(\Heritage\Database\Connection, string, \Closure|null): \Heritage\Database\Schema\Blueprint
     */
    protected $resolver;

    /**
     * The default string length for migrations.
     *
     * @var non-negative-int|null
     */
    public static $defaultStringLength = 255;

    /**
     * The default time precision for migrations.
     */
    public static ?int $defaultTimePrecision = 0;

    /**
     * The default relationship morph key type.
     *
     * @var 'int'|'uuid'|'ulid'
     */
    public static $defaultMorphKeyType = 'int';

    /**
     * Create a new database Schema manager.
     *
     * @param  \Heritage\Database\Connection  $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * Set the default string length for migrations.
     *
     * @param  non-negative-int  $length
     * @return void
     */
    public static function defaultStringLength($length)
    {
        static::$defaultStringLength = $length;
    }

    /**
     * Set the default time precision for migrations.
     */
    public static function defaultTimePrecision(?int $precision): void
    {
        static::$defaultTimePrecision = $precision;
    }

    /**
     * Set the default morph key type for migrations.
     *
     * @param  string  $type
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public static function defaultMorphKeyType(string $type)
    {
        if (! in_array($type, ['int', 'uuid', 'ulid'])) {
            throw new InvalidArgumentException("Morph key type must be 'int', 'uuid', or 'ulid'.");
        }

        static::$defaultMorphKeyType = $type;
    }

    /**
     * Set the default morph key type for migrations to UUIDs.
     *
     * @return void
     */
    public static function morphUsingUuids()
    {
        static::defaultMorphKeyType('uuid');
    }

    /**
     * Set the default morph key type for migrations to ULIDs.
     *
     * @return void
     */
    public static function morphUsingUlids()
    {
        static::defaultMorphKeyType('ulid');
    }

    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     */
    public function createDatabase($name)
    {
        return $this->connection->statement(
            $this->grammar->compileCreateDatabase($name)
        );
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function dropDatabaseIfExists($name)
    {
        return $this->connection->statement(
            $this->grammar->compileDropDatabaseIfExists($name)
        );
    }

    /**
     * Get the schemas that belong to the connection.
     *
     * @return list<array{name: string, path: string|null, default: bool}>
     */
    public function getSchemas()
    {
        return $this->connection->getPostProcessor()->processSchemas(
            $this->connection->selectFromWriteConnection($this->grammar->compileSchemas())
        );
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        if ($sql = $this->grammar->compileTableExists($schema, $table)) {
            return (bool) $this->connection->scalar($sql);
        }

        foreach ($this->getTables($schema ?? $this->getCurrentSchemaName()) as $value) {
            if (strtolower($table) === strtolower($value['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the given view exists.
     *
     * @param  string  $view
     * @return bool
     */
    public function hasView($view)
    {
        [$schema, $view] = $this->parseSchemaAndTable($view);

        $view = $this->connection->getTablePrefix().$view;

        foreach ($this->getViews($schema ?? $this->getCurrentSchemaName()) as $value) {
            if (strtolower($view) === strtolower($value['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the tables that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @return list<array{name: string, schema: string|null, schema_qualified_name: string, size: int|null, comment: string|null, collation: string|null, engine: string|null}>
     */
    public function getTables($schema = null)
    {
        return $this->connection->getPostProcessor()->processTables(
            $this->connection->selectFromWriteConnection($this->grammar->compileTables($schema))
        );
    }

    /**
     * Get the names of the tables that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @param  bool  $schemaQualified
     * @return list<string>
     */
    public function getTableListing($schema = null, $schemaQualified = true)
    {
        return array_column(
            $this->getTables($schema),
            $schemaQualified ? 'schema_qualified_name' : 'name'
        );
    }

    /**
     * Get the views that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @return list<array{name: string, schema: string|null, schema_qualified_name: string, definition: string}>
     */
    public function getViews($schema = null)
    {
        return $this->connection->getPostProcessor()->processViews(
            $this->connection->selectFromWriteConnection($this->grammar->compileViews($schema))
        );
    }

    /**
     * Get the user-defined types that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @return list<array{name: string, schema: string, schema_qualified_name: string, type: string, category: string, implicit: bool}>
     */
    public function getTypes($schema = null)
    {
        return $this->connection->getPostProcessor()->processTypes(
            $this->connection->selectFromWriteConnection($this->grammar->compileTypes($schema))
        );
    }

    /**
     * Determine if the given table has a given column.
     *
     * @param  string  $table
     * @param  string  $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return in_array(
            strtolower($column), array_map(strtolower(...), $this->getColumnListing($table))
        );
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string  $table
     * @param  array<string>  $columns
     * @return bool
     */
    public function hasColumns($table, array $columns)
    {
        $tableColumns = array_map(strtolower(...), $this->getColumnListing($table));

        return array_all($columns, fn ($column) => in_array(strtolower($column), $tableColumns));
    }

    /**
     * Execute a table builder callback if the given table has a given column.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  \Closure  $callback
     * @return void
     */
    public function whenTableHasColumn(string $table, string $column, Closure $callback)
    {
        if ($this->hasColumn($table, $column)) {
            $this->table($table, fn (Blueprint $table) => $callback($table));
        }
    }

    /**
     * Execute a table builder callback if the given table doesn't have a given column.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  \Closure  $callback
     * @return void
     */
    public function whenTableDoesntHaveColumn(string $table, string $column, Closure $callback)
    {
        if (! $this->hasColumn($table, $column)) {
            $this->table($table, fn (Blueprint $table) => $callback($table));
        }
    }

    /**
     * Execute a table builder callback if the given table has a given index.
     *
     * @param  string  $table
     * @param  string|array  $index
     * @param  \Closure  $callback
     * @param  string|null  $type
     * @return void
     */
    public function whenTableHasIndex(string $table, string|array $index, Closure $callback, ?string $type = null)
    {
        if ($this->hasIndex($table, $index, $type)) {
            $this->table($table, fn (Blueprint $table) => $callback($table));
        }
    }

    /**
     * Execute a table builder callback if the given table doesn't have a given index.
     *
     * @param  string  $table
     * @param  string|array  $index
     * @param  \Closure  $callback
     * @param  string|null  $type
     * @return void
     */
    public function whenTableDoesntHaveIndex(string $table, string|array $index, Closure $callback, ?string $type = null)
    {
        if (! $this->hasIndex($table, $index, $type)) {
            $this->table($table, fn (Blueprint $table) => $callback($table));
        }
    }

    /**
     * Get the data type for the given column name.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  bool  $fullDefinition
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getColumnType($table, $column, $fullDefinition = false)
    {
        $columns = $this->getColumns($table);

        foreach ($columns as $value) {
            if (strtolower($value['name']) === strtolower($column)) {
                return $fullDefinition ? $value['type'] : $value['type_name'];
            }
        }

        throw new InvalidArgumentException("There is no column with name '$column' on table '$table'.");
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     * @return list<string>
     */
    public function getColumnListing($table)
    {
        return array_column($this->getColumns($table), 'name');
    }

    /**
     * Get the columns for a given table.
     *
     * @param  string  $table
     * @return list<array{name: string, type: string, type_name: string, collation: string|null, nullable: bool, default: mixed, auto_increment: bool, comment: string|null, generation: array{type: string, expression: string|null}|null}>
     */
    public function getColumns($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processColumns(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileColumns($schema, $table)
            )
        );
    }

    /**
     * Get the indexes for a given table.
     *
     * @param  string  $table
     * @return list<array{name: string, columns: list<string>, type: string, unique: bool, primary: bool}>
     */
    public function getIndexes($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processIndexes(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileIndexes($schema, $table)
            )
        );
    }

    /**
     * Get the names of the indexes for a given table.
     *
     * @param  string  $table
     * @return list<string>
     */
    public function getIndexListing($table)
    {
        return array_column($this->getIndexes($table), 'name');
    }

    /**
     * Determine if the given table has a given index.
     *
     * @param  string  $table
     * @param  string|array  $index
     * @param  string|null  $type
     * @return bool
     */
    public function hasIndex($table, $index, $type = null)
    {
        $type = is_null($type) ? $type : strtolower($type);

        $index = is_string($index) ? strtolower($index) : $index;

        foreach ($this->getIndexes($table) as $value) {
            $typeMatches = is_null($type)
                || ($type === 'primary' && $value['primary'])
                || ($type === 'unique' && $value['unique'])
                || $type === $value['type'];

            if (($value['name'] === $index || $value['columns'] === $index) && $typeMatches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the table has a given foreign key.
     *
     * @param  string  $table
     * @param  array|string  $foreignKey
     * @return bool
     */
    public function hasForeignKey($table, $foreignKey)
    {
        foreach ($this->getForeignKeys($table) as $value) {
            if ($value['name'] === $foreignKey || $value['columns'] === $foreignKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the foreign keys for a given table.
     *
     * @param  string  $table
     * @return list<array{name: string|null, columns: list<string>, foreign_schema: string|null, foreign_table: string, foreign_columns: list<string>, on_update: string|null, on_delete: string|null}>
     */
    public function getForeignKeys($table)
    {
        [$schema, $table] = $this->parseSchemaAndTable($table);

        $table = $this->connection->getTablePrefix().$table;

        return $this->connection->getPostProcessor()->processForeignKeys(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileForeignKeys($schema, $table)
            )
        );
    }

    /**
     * Modify a table on the schema.
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->build($this->createBlueprint($table, $callback));
    }

    /**
     * Create a new table on the schema.
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    public function create($table, Closure $callback)
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($callback) {
            $blueprint->create();

            $callback($blueprint);
        }));
    }

    /**
     * Create a new translatable table and its paired translation table on the schema.
     *
     * Options:
     *  - table:         translation table name (default "{singular}_translations")
     *  - foreign_key:   column referencing the main table (default "{singular}_id")
     *  - locale_column: defaults to the LocaleResolver's column ("locale_id" with i18n, else "locale")
     *  - locale_length: length of a "locale" code column (default 10)
     *  - timestamps:    translation rows keep their own timestamps (default true)
     *  - translation:   Closure customizing the translation table blueprint
     * A translatable column marked unique() is unique per locale. A column marked withTranslations()
     * stays on the main table and is added to the translation table too: softDeletes()->withTranslations()
     * soft deletes translation rows, and the per-locale unique indexes then ignore trashed rows where
     * the database supports partial indexes (not MySQL / MariaDB).
     *
     * @param  string  $table
     * @param  \Closure(\Heritage\Database\Schema\Blueprint): void  $callback
     * @param  array{table?: string, foreign_key?: string, locale_column?: string, locale_length?: int, timestamps?: bool, translation?: \Closure(\Heritage\Database\Schema\Blueprint): void}  $options
     * @return void
     */
    public function createTranslatable($table, Closure $callback, array $options = [])
    {
        if ($unknown = array_diff(array_keys($options), ['table', 'foreign_key', 'locale_column', 'locale_length', 'timestamps', 'translation'])) {
            throw new InvalidArgumentException('Unknown createTranslatable() options: '.implode(', ', $unknown).'.');
        }

        [$schema, $tableName] = $this->parseSchemaAndTable($table);
        $defaultTranslationName = Str::singular($tableName).'_translations';
        $resolver = Locales::resolver();

        $translationCallback = $options['translation'] ?? null;
        $translationTable = $options['table'] ?? (isset($schema) ? "{$schema}.{$defaultTranslationName}" : $defaultTranslationName);
        $foreignKey = $options['foreign_key'] ?? Str::singular($tableName).'_id';
        // "locale_id" (FK to the resolver's table) with the i18n artifact, a "locale" code column otherwise.
        $localeColumn = $options['locale_column'] ?? $resolver->storageColumn();
        $localeTable = $localeColumn === $resolver->storageColumn() ? $resolver->storageTable() : null;
        $localeLength = $options['locale_length'] ?? 10;
        // Translation rows keep their own timestamps unless disabled.
        $includeTimestamps = $options['timestamps'] ?? true;
        $partialUniques = [];

        // 1. Build the main table blueprint first to collect all column definitions
        $mainBlueprint = $this->createBlueprint($table);
        $mainBlueprint->create();

        $callback($mainBlueprint);

        // 2. Identify and extract translation columns
        /** @var \Heritage\Database\Schema\ColumnDefinition[] $translationColumns */
        $translationColumns = [];

        foreach ($mainBlueprint->getColumns() as $column) {
            if ($column instanceof ColumnDefinition && $column->isTranslatable()) {
                $translationColumns[] = $column;
            }
        }

        // Remove translation columns from the main table blueprint
        foreach ($translationColumns as $column) {
            $mainBlueprint->removeColumn($column->name);
        }

        // Columns kept on the main table and added to the translation table as well
        /** @var \Heritage\Database\Schema\ColumnDefinition[] $sharedColumns */
        $sharedColumns = [];
        $softDeleteColumn = null;

        foreach ($mainBlueprint->getColumns() as $column) {
            if ($column instanceof ColumnDefinition && $column->isWithTranslations()) {
                $sharedColumns[] = $column;

                if ($column->isSoftDeletes()) {
                    $softDeleteColumn = $column->name;
                }
            }
        }

        // 3. Determine the primary key type and name of the main table
        $primaryKeyType = 'id';
        $primaryKeyName = 'id';

        foreach ($mainBlueprint->getColumns() as $column) {
            if ($column->autoIncrement || $column->primary || $column->name === 'id') {
                $primaryKeyName = $column->name;
                if ($column->type === 'uuid') {
                    $primaryKeyType = 'uuid';
                } elseif ($column->type === 'ulid') {
                    $primaryKeyType = 'ulid';
                } elseif ($column->type === 'integer') {
                    $primaryKeyType = 'integer';
                }
                break;
            }
        }

        // 4. Build and execute the main table
        $this->build($mainBlueprint);

        // 5. Build and execute the translation table
        $this->create($translationTable, function (Blueprint $tableBlueprint) use (
            $table,
            $translationColumns,
            $primaryKeyType,
            $primaryKeyName,
            $foreignKey,
            $localeColumn,
            $localeTable,
            $localeLength,
            $translationCallback,
            $includeTimestamps,
            $sharedColumns,
            $softDeleteColumn,
            &$partialUniques
        ) {
            // Primary key on translation table
            if ($primaryKeyType === 'uuid') {
                $tableBlueprint->uuid('id')->primary();
                $tableBlueprint->foreignUuid($foreignKey)->constrained($table, $primaryKeyName)->cascadeOnDelete();
            } elseif ($primaryKeyType === 'ulid') {
                $tableBlueprint->ulid('id')->primary();
                $tableBlueprint->foreignUlid($foreignKey)->constrained($table, $primaryKeyName)->cascadeOnDelete();
            } elseif ($primaryKeyType === 'integer') {
                $tableBlueprint->increments('id');
                $tableBlueprint->unsignedInteger($foreignKey);
                $tableBlueprint->foreign($foreignKey)->references($primaryKeyName)->on($table)->cascadeOnDelete();
            } else {
                $tableBlueprint->id();
                $tableBlueprint->foreignId($foreignKey)->constrained($table, $primaryKeyName)->cascadeOnDelete();
            }

            // Locale column
            if ($localeTable !== null) {
                $tableBlueprint->foreignId($localeColumn)->constrained($localeTable)->cascadeOnDelete();
            } else {
                $tableBlueprint->string($localeColumn, $localeLength)->index();
            }

            // Add all translated columns; a unique translated column is unique per locale
            $uniquePerLocale = [];

            foreach ($translationColumns as $column) {
                $clonedAttributes = $column->getAttributes();

                if (! empty($clonedAttributes['unique'])) {
                    $uniquePerLocale[] = $clonedAttributes['name'];
                }

                unset(
                    $clonedAttributes['translation'],
                    $clonedAttributes['translatable'],
                    $clonedAttributes['translate'],
                    $clonedAttributes['after'],
                    $clonedAttributes['unique']
                );

                $clonedColumn = new ColumnDefinition($clonedAttributes);
                $tableBlueprint->addColumnDefinition($clonedColumn);
            }

            foreach ($sharedColumns as $column) {
                $sharedAttributes = $column->getAttributes();

                unset(
                    $sharedAttributes['withTranslations'],
                    $sharedAttributes['after'],
                    $sharedAttributes['first'],
                    $sharedAttributes['unique'],
                    $sharedAttributes['index'],
                    $sharedAttributes['primary']
                );

                $tableBlueprint->addColumnDefinition(new ColumnDefinition($sharedAttributes));
            }

            // Optional extra customizations on the translation table
            if ($translationCallback instanceof Closure) {
                $translationCallback($tableBlueprint);
            }

            foreach ($uniquePerLocale as $name) {
                if ($softDeleteColumn !== null && $this->supportsPartialIndexes()) {
                    $partialUniques[] = [$localeColumn, $name];
                } else {
                    $tableBlueprint->unique([$localeColumn, $name]);
                }
            }

            // One row per locale, trashed or not: re-adding a soft-deleted locale restores its row.
            $tableBlueprint->unique([$foreignKey, $localeColumn]);

            if ($includeTimestamps) {
                $tableBlueprint->timestamps();
            }
        });

        foreach ($partialUniques as $columns) {
            $this->createPartialUnique($translationTable, $columns, $softDeleteColumn);
        }
    }

    /**
     * Whether the connection supports partial (filtered) indexes; MySQL and MariaDB do not.
     *
     * @return bool
     */
    public function supportsPartialIndexes(): bool
    {
        return in_array($this->connection->getDriverName(), ['pgsql', 'sqlite', 'sqlsrv'], true);
    }

    /**
     * Create a unique index covering only the rows that are not soft deleted.
     *
     * @param  string  $table
     * @param  array<int, string>  $columns
     * @param  string  $deletedAtColumn
     * @return void
     */
    protected function createPartialUnique(string $table, array $columns, string $deletedAtColumn): void
    {
        $name = str_replace(['-', '.'], '_', strtolower(
            $this->connection->getTablePrefix().$table.'_'.implode('_', $columns).'_unique'
        ));

        $this->connection->statement(sprintf(
            'create unique index %s on %s (%s) where %s is null',
            $this->grammar->wrap($name),
            $this->grammar->wrapTable($table),
            $this->grammar->columnize($columns),
            $this->grammar->wrap($deletedAtColumn)
        ));
    }

    /**
     * Drop a translatable table and its paired translation table from the schema.
     *
     * @param  string  $table
     * @param  string|null  $translationTable
     * @return void
     */
    public function dropTranslatable($table, ?string $translationTable = null)
    {
        [$schema, $tableName] = $this->parseSchemaAndTable($table);
        $defaultTranslationName = Str::singular($tableName).'_translations';
        $translationTable = $translationTable ?? (isset($schema) ? "{$schema}.{$defaultTranslationName}" : $defaultTranslationName);

        $this->dropIfExists($translationTable);
        $this->drop($table);
    }

    /**
     * Drop a translatable table and its paired translation table from the schema if they exist.
     *
     * @param  string  $table
     * @param  string|null  $translationTable
     * @return void
     */
    public function dropIfExistsTranslatable($table, ?string $translationTable = null)
    {
        [$schema, $tableName] = $this->parseSchemaAndTable($table);
        $defaultTranslationName = Str::singular($tableName).'_translations';
        $translationTable = $translationTable ?? (isset($schema) ? "{$schema}.{$defaultTranslationName}" : $defaultTranslationName);

        $this->dropIfExists($translationTable);
        $this->dropIfExists($table);
    }

    /**
     * Drop a table from the schema.
     *
     * @param  string  $table
     * @return void
     */
    public function drop($table)
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->drop();
        }));
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param  string  $table
     * @return void
     */
    public function dropIfExists($table)
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->dropIfExists();
        }));
    }

    /**
     * Drop columns from a table schema.
     *
     * @param  string  $table
     * @param  string|array<string>  $columns
     * @return void
     */
    public function dropColumns($table, $columns)
    {
        $this->table($table, function (Blueprint $blueprint) use ($columns) {
            $blueprint->dropColumn($columns);
        });
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllTables()
    {
        throw new LogicException('This database driver does not support dropping all tables.');
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllViews()
    {
        throw new LogicException('This database driver does not support dropping all views.');
    }

    /**
     * Drop all types from the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllTypes()
    {
        throw new LogicException('This database driver does not support dropping all types.');
    }

    /**
     * Rename a table on the schema.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function rename($from, $to)
    {
        $this->build(tap($this->createBlueprint($from), function ($blueprint) use ($to) {
            $blueprint->rename($to);
        }));
    }

    /**
     * Enable foreign key constraints.
     *
     * @return bool
     */
    public function enableForeignKeyConstraints()
    {
        return $this->connection->statement(
            $this->grammar->compileEnableForeignKeyConstraints()
        );
    }

    /**
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disableForeignKeyConstraints()
    {
        return $this->connection->statement(
            $this->grammar->compileDisableForeignKeyConstraints()
        );
    }

    /**
     * Disable foreign key constraints during the execution of a callback.
     *
     * @template TReturn
     *
     * @param  (\Closure(): TReturn)  $callback
     * @return TReturn
     */
    public function withoutForeignKeyConstraints(Closure $callback)
    {
        $this->disableForeignKeyConstraints();

        try {
            return $callback();
        } finally {
            $this->enableForeignKeyConstraints();
        }
    }

    /**
     * Create the vector extension on the schema if it does not exist.
     *
     * @param  string|null  $schema
     * @return void
     */
    public function ensureVectorExtensionExists($schema = null)
    {
        $this->ensureExtensionExists('vector', $schema);
    }

    /**
     * Create a new extension on the schema if it does not exist.
     *
     * @param  string  $name
     * @param  string|null  $schema
     * @return void
     *
     * @throws \RuntimeException
     */
    public function ensureExtensionExists($name, $schema = null)
    {
        if (! $this->getConnection() instanceof PostgresConnection) {
            throw new RuntimeException('Extensions are only supported by Postgres.');
        }

        $name = $this->getConnection()->getSchemaGrammar()->wrap($name);

        $this->getConnection()->statement(match (filled($schema)) {
            true => "create extension if not exists {$name} schema {$this->getConnection()->getSchemaGrammar()->wrap($schema)}",
            false => "create extension if not exists {$name}",
        });
    }

    /**
     * Execute the blueprint to build / modify the table.
     *
     * @param  \Heritage\Database\Schema\Blueprint  $blueprint
     * @return void
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build();
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @return \Heritage\Database\Schema\Blueprint
     */
    protected function createBlueprint($table, ?Closure $callback = null)
    {
        $connection = $this->connection;

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $connection, $table, $callback);
        }

        return Container::getInstance()->make(Blueprint::class, ['connection' => $connection, 'table' => $table, 'callback' => $callback]);
    }

    /**
     * Get the names of the current schemas for the connection.
     *
     * @return string[]|null
     */
    public function getCurrentSchemaListing()
    {
        return null;
    }

    /**
     * Get the default schema name for the connection.
     *
     * @return string|null
     */
    public function getCurrentSchemaName()
    {
        return $this->getCurrentSchemaListing()[0] ?? null;
    }

    /**
     * Parse the given database object reference and extract the schema and table.
     *
     * @param  string  $reference
     * @param  string|bool|null  $withDefaultSchema
     * @return array{string|null, string}
     *
     * @throws \InvalidArgumentException
     */
    public function parseSchemaAndTable($reference, $withDefaultSchema = null)
    {
        $segments = explode('.', $reference);

        if (count($segments) > 2) {
            throw new InvalidArgumentException(
                "Using three-part references is not supported, you may use `Schema::connection('{$segments[0]}')` instead."
            );
        }

        $table = $segments[1] ?? $segments[0];

        $schema = match (true) {
            isset($segments[1]) => $segments[0],
            is_string($withDefaultSchema) => $withDefaultSchema,
            $withDefaultSchema => $this->getCurrentSchemaName(),
            default => null,
        };

        return [$schema, $table];
    }

    /**
     * Get the database connection instance.
     *
     * @return \Heritage\Database\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param  \Closure(\Heritage\Database\Connection, string, \Closure|null): \Heritage\Database\Schema\Blueprint  $resolver
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }
}
