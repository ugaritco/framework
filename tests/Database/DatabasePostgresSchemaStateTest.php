<?php

namespace Heritage\Tests\Database;

use Heritage\Database\Connection;
use Heritage\Database\Schema\PostgresSchemaState;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabasePostgresSchemaStateTest extends TestCase
{
    public function testBaseVariablesUseConfiguredConnectionByDefault()
    {
        $schemaState = new PostgresSchemaState(new Connection(new DatabasePostgresSchemaStateTestMockPDO));

        $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke($schemaState, [
            'host' => 'pooler-host',
            'port' => '6432',
            'username' => 'root',
            'password' => 'secret',
            'database' => 'ugarit',
            'sslmode' => 'prefer',
        ]);

        $this->assertSame([
            'UGARIT_LOAD_HOST' => 'pooler-host',
            'UGARIT_LOAD_PORT' => '6432',
            'UGARIT_LOAD_USER' => 'root',
            'PGPASSWORD' => 'secret',
            'PGSSLMODE' => 'prefer',
            'UGARIT_LOAD_DATABASE' => 'ugarit',
        ], $variables);
    }

    public function testBaseVariablesUseDirectConnectionConfigurationWhenAvailable()
    {
        $connection = new Connection(new DatabasePostgresSchemaStateTestMockPDO);
        $connection->setDirectPdoConfig([
            'host' => ['direct-host', 'direct-host-2'],
            'port' => '5432',
            'username' => 'direct_user',
            'password' => 'direct_secret',
            'database' => 'direct_database',
            'sslmode' => 'require',
        ]);

        $schemaState = new PostgresSchemaState($connection);

        $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke($schemaState, [
            'host' => 'pooler-host',
            'port' => '6432',
            'username' => 'root',
            'password' => 'secret',
            'database' => 'ugarit',
            'sslmode' => 'prefer',
        ]);

        $this->assertSame([
            'UGARIT_LOAD_HOST' => 'direct-host',
            'UGARIT_LOAD_PORT' => '5432',
            'UGARIT_LOAD_USER' => 'direct_user',
            'PGPASSWORD' => 'direct_secret',
            'PGSSLMODE' => 'require',
            'UGARIT_LOAD_DATABASE' => 'direct_database',
        ], $variables);
    }

    public function testBaseVariablesDoNotExportEmptySslMode()
    {
        $schemaState = new PostgresSchemaState(new Connection(new DatabasePostgresSchemaStateTestMockPDO));

        $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke($schemaState, [
            'host' => 'pooler-host',
            'port' => '6432',
            'username' => 'root',
            'password' => 'secret',
            'database' => 'ugarit',
        ]);

        $this->assertSame([
            'UGARIT_LOAD_HOST' => 'pooler-host',
            'UGARIT_LOAD_PORT' => '6432',
            'UGARIT_LOAD_USER' => 'root',
            'PGPASSWORD' => 'secret',
            'UGARIT_LOAD_DATABASE' => 'ugarit',
        ], $variables);
    }
}

class DatabasePostgresSchemaStateTestMockPDO extends PDO
{
    public function __construct()
    {
        //
    }
}
