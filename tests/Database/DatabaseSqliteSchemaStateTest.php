<?php

namespace Heritage\Tests\Database;

use Heritage\Database\Schema\SqliteSchemaState;
use Heritage\Database\SQLiteConnection;
use Heritage\Filesystem\Filesystem;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DatabaseSqliteSchemaStateTest extends TestCase
{
    public function testLoadSchemaToDatabase(): void
    {
        $config = ['driver' => 'sqlite', 'database' => 'database/database.sqlite', 'prefix' => '', 'foreign_key_constraints' => true, 'name' => 'sqlite'];
        $connection = Mockery::mock(SQLiteConnection::class);
        $connection->expects('getConfig')->andReturn($config);
        $connection->expects('getDatabaseName')->andReturn($config['database']);

        $process = Mockery::spy(Process::class);
        $command = null;
        $processFactory = function ($givenCommand) use ($process, &$command) {
            $command = $givenCommand;

            return $process;
        };

        $schemaState = new SqliteSchemaState($connection, null, $processFactory);
        $schemaState->load('database/schema/sqlite-schema.dump');

        $this->assertSame('sqlite3 "${:UGARIT_LOAD_DATABASE}" < "${:UGARIT_LOAD_PATH}"', $command);

        $process->shouldHaveReceived('mustRun')->with(null, [
            'UGARIT_LOAD_DATABASE' => 'database/database.sqlite',
            'UGARIT_LOAD_PATH' => 'database/schema/sqlite-schema.dump',
        ]);
    }

    public function testLoadSchemaToInMemory(): void
    {
        $config = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true, 'name' => 'sqlite'];
        $connection = Mockery::mock(SQLiteConnection::class);
        $connection->expects('getDatabaseName')->andReturn($config['database']);
        $pdo = Mockery::spy(PDO::class);
        $connection->expects('getPdo')->andReturn($pdo);

        $files = Mockery::mock(Filesystem::class);
        $files->expects('get')->andReturn('CREATE TABLE IF NOT EXISTS "migrations" ("id" integer not null primary key autoincrement, "migration" varchar not null, "batch" integer not null);');

        $schemaState = new SqliteSchemaState($connection, $files);
        $schemaState->load('database/schema/sqlite-schema.dump');

        $pdo->shouldHaveReceived('exec')->with('CREATE TABLE IF NOT EXISTS "migrations" ("id" integer not null primary key autoincrement, "migration" varchar not null, "batch" integer not null);');
    }
}
