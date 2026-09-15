<?php

namespace Heritage\Tests\Database;

use Heritage\Container\Container;
use Heritage\Database\Connection;
use Heritage\Database\Schema\SQLiteBuilder;
use Heritage\Filesystem\Filesystem;
use Heritage\Support\Facades\Facade;
use Heritage\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseSQLiteBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        $app = new Container;

        Container::setInstance($app)
            ->singleton('files', Filesystem::class);

        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::setFacadeApplication(null);
    }

    public function testCreateDatabase()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getSchemaGrammar');

        $builder = new SQLiteBuilder($connection);

        File::expects('put')
            ->with('my_temporary_database_a', '')
            ->andReturn(20); // bytes

        $this->assertTrue($builder->createDatabase('my_temporary_database_a'));

        File::expects('put')
            ->with('my_temporary_database_b', '')
            ->andReturn(false);

        $this->assertFalse($builder->createDatabase('my_temporary_database_b'));
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('getSchemaGrammar');

        $builder = new SQLiteBuilder($connection);

        File::expects('exists')
            ->andReturn(true);

        File::expects('delete')
            ->with('my_temporary_database_b')
            ->andReturn(true);

        $this->assertTrue($builder->dropDatabaseIfExists('my_temporary_database_b'));

        File::expects('exists')
            ->andReturn(false);

        $this->assertTrue($builder->dropDatabaseIfExists('my_temporary_database_c'));

        File::expects('exists')
            ->andReturn(true);

        File::expects('delete')
            ->with('my_temporary_database_c')
            ->andReturn(false);

        $this->assertFalse($builder->dropDatabaseIfExists('my_temporary_database_c'));
    }
}
