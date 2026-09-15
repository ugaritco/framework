<?php

namespace Heritage\Tests\Integration\Database\MySql;

use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\DB;
use Heritage\Support\Facades\Schema;

class DatabaseExplainTest extends MySqlTestCase
{
    protected function afterRefreshingDatabase()
    {
        if (! Schema::hasTable('db_explain_tbl')) {
            Schema::create('db_explain_tbl', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('db_explain_tbl');
    }

    public function testResultIsAnObject()
    {
        DB::table('db_explain_tbl')->insert(['name' => 'taylor']);

        $result = DB::table('db_explain_tbl')->where('name', 'taylor')->explain();

        $this->assertIsObject($result);
        $this->assertEquals(1, $result->count());
        $this->assertIsObject($result->first());
    }
}
