<?php

namespace Heritage\Tests\Database;

use Heritage\Database\Connection;
use Heritage\Database\Query\Grammars\SQLiteGrammar;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseSQLiteQueryGrammarTest extends TestCase
{
    public function testToRawSql()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->expects('escape')->with('foo', false)->andReturn("'foo'");
        $grammar = new SQLiteGrammar($connection);

        $query = $grammar->substituteBindingsIntoRawSql(
            'select * from "users" where \'Hello\'\'World?\' IS NOT NULL AND "email" = ?',
            ['foo'],
        );

        $this->assertSame('select * from "users" where \'Hello\'\'World?\' IS NOT NULL AND "email" = \'foo\'', $query);
    }
}
