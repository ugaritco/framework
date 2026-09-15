<?php

namespace Heritage\Tests\Integration\Database\MariaDb;

use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\DB;
use Heritage\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_mysql')]
class DatabaseMariaDbSchemaBuilderTest extends MariaDbTestCase
{
    public function testAddCommentToTable()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->comment('This is a comment');
        });

        $tableInfo = DB::table('information_schema.tables')
            ->where('table_schema', $this->app['config']->get('database.connections.mariadb.database'))
            ->where('table_name', 'users')
            ->select('table_comment as table_comment')
            ->first();

        $this->assertSame('This is a comment', $tableInfo->table_comment);

        Schema::drop('users');
    }
}
