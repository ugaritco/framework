<?php

namespace Heritage\Tests\Testing\Concerns;

use Heritage\Database\Connection;
use Heritage\Database\Query\Expression;
use Heritage\Foundation\Testing\Concerns\InteractsWithDatabase;
use Heritage\Support\Facades\DB;
use Heritage\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;

class InteractsWithDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testCastToJsonSqlite()
    {
        $grammar = 'SQLite';

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '{"foo":"bar"}'
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonPostgres()
    {
        $grammar = 'Postgres';

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '{"foo":"bar"}'
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonSqlServer()
    {
        $grammar = 'SqlServer';

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]')
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]')
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('{"foo":"bar"}')
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonMySql()
    {
        $grammar = 'MySql';

        $this->assertEquals(<<<'TEXT'
        cast('["foo","bar"]' as json)
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        cast('["foo","bar"]' as json)
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        cast('{"foo":"bar"}' as json)
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonMariaDb()
    {
        $grammar = 'MariaDb';

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]', '$')
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]', '$')
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('{"foo":"bar"}', '$')
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    protected function castAsJson($value, $grammar)
    {
        $connection = Mockery::mock(Connection::class);
        $grammarClass = 'Heritage\Database\Query\Grammars\\'.$grammar.'Grammar';
        $grammar = new $grammarClass($connection);

        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);

        $connection->shouldReceive('raw')->andReturnUsing(function ($value) {
            return new Expression($value);
        });

        $connection->shouldReceive('getPdo->quote')->andReturnUsing(function ($value) {
            return "'".$value."'";
        });

        DB::shouldReceive('connection')->with(null)->andReturn($connection);

        $instance = new class
        {
            use InteractsWithDatabase;
        };

        return $instance->castAsJson($value)->getValue($grammar);
    }
}
