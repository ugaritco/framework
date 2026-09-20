<?php

declare(strict_types=1);

namespace Heritage\Database;

use InvalidArgumentException;

/**
 * Class ArtifactSeeder
 *
 * Abstract base seeder designed for domain artifacts in the Ugarit Ecosystem.
 * Allows orchestration and selective execution of entity table seeders either globally
 * or as requested by application seeders (such as AppSeeder).
 */
abstract class ArtifactSeeder extends Seeder
{
    /**
     * Map of domain table names to their corresponding dedicated Seeder class strings.
     *
     * @var array<string, class-string<\Heritage\Database\Seeder>>
     */
    protected array $tableSeeders = [];

    /**
     * Specific list of target tables configured for this execution run.
     *
     * @var array<int, string>
     */
    protected array $tables = [];

    /**
     * Instantiate the artifact seeder configured specifically for the given tables.
     *
     * @param  array<int, string>  $tables
     * @return static
     */
    public static function forTables(array $tables): static
    {
        $instance = new static();
        $instance->tables = $tables;

        return $instance;
    }

    /**
     * Run the database seeders for the registered artifact tables.
     *
     * @param  array<int, string>  $tables  Optional list of tables to execute (overrides instance property).
     * @return void
     *
     * @throws \InvalidArgumentException When an unknown table name is requested.
     */
    public function run(array $tables = []): void
    {
        // Determine the target tables: from method argument, or instance property, or all defined table seeders
        $targetTables = ! empty($tables)
            ? $tables
            : (! empty($this->tables) ? $this->tables : array_keys($this->tableSeeders));

        foreach ($targetTables as $table) {
            // Verify that the requested table has an assigned seeder class
            if (! isset($this->tableSeeders[$table])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'No seeder registered for table [%s] in artifact seeder [%s]. Registered tables: [%s].',
                        $table,
                        static::class,
                        implode(', ', array_keys($this->tableSeeders))
                    )
                );
            }

            $seederClass = $this->tableSeeders[$table];

            // Execute the table seeder
            $this->call($seederClass);
        }
    }

    /**
     * Retrieve the full map of registered table seeders for this artifact.
     *
     * @return array<string, class-string<\Heritage\Database\Seeder>>
     */
    public function getTableSeeders(): array
    {
        return $this->tableSeeders;
    }

    /**
     * Set or override the table seeders map dynamically.
     *
     * @param  array<string, class-string<\Heritage\Database\Seeder>>  $tableSeeders
     * @return $this
     */
    public function setTableSeeders(array $tableSeeders): static
    {
        $this->tableSeeders = $tableSeeders;

        return $this;
    }
}
