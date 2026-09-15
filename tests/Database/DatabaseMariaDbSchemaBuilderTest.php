<?php

namespace Heritage\Tests\Database;

use Heritage\Database\Connection;
use Heritage\Database\Query\Processors\MariaDbProcessor;
use Heritage\Database\Schema\Grammars\MariaDbGrammar;
use Heritage\Database\Schema\MariaDbBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseMariaDbSchemaBuilderTest extends TestCase
{
    public function testHasTable()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(MariaDbGrammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = new MariaDbBuilder($connection);
        $grammar->expects('compileTableExists')->andReturn('sql');
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('scalar')->with('sql')->andReturn(1);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(MariaDbGrammar::class);
        $processor = Mockery::mock(MariaDbProcessor::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $grammar->expects('compileColumns')->with(null, 'prefix_table')->andReturn('sql');
        $processor->expects('processColumns')->andReturn([['name' => 'column']]);
        $builder = new MariaDbBuilder($connection);
        $connection->expects('getTablePrefix')->andReturn('prefix_');
        $connection->expects('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'column']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
