<?php

namespace Heritage\Tests\Database;

use Heritage\Database\Connection;
use Heritage\Database\Schema\Grammars\Grammar;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseAbstractSchemaGrammarTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new class($connection) extends Grammar {
        };

        $this->assertSame('create database "foo"', $grammar->compileCreateDatabase('foo'));
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new class($connection) extends Grammar {
        };

        $this->assertSame('drop database if exists "foo"', $grammar->compileDropDatabaseIfExists('foo'));
    }
}
