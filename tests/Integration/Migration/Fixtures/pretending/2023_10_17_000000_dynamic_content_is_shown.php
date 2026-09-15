<?php

use Heritage\Database\Migrations\Migration;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\DB;
use Heritage\Support\Facades\Schema;

class DynamicContentIsShown extends Migration
{
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url')->nullable();
            $table->string('name')->nullable();
        });

        DB::table('blogs')->insert([
            ['url' => 'www.janedoe.com'],
            ['url' => 'www.johndoe.com'],
        ]);

        DB::statement("ALTER TABLE 'pseudo_table_name' MODIFY 'column_name' VARCHAR(191)");

        /** @var \Heritage\Support\Collection $tablesList */
        $tablesList = DB::withoutPretending(function () {
            return DB::table('people')->get();
        });

        $tablesList->each(function ($person, $key) {
            DB::table('blogs')->where('blog_id', '=', $person->blog_id)->insert([
                'id' => $key + 1,
                'name' => "{$person->name} Blog",
            ]);
        });
    }
}
