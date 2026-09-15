<?php

namespace Heritage\Tests\Integration\Database\EloquentMorphToTouchesTest;

use DB;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Database\Fixtures\Models\MorphToTarget\Post;
use Heritage\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphToTouchesTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->nullableMorphs('commentable');
        });

        Post::create();
    }

    public function testNotNull()
    {
        $comment = (new Comment)->commentable()->associate(Post::first());

        DB::enableQueryLog();

        $comment->save();

        $this->assertCount(2, DB::getQueryLog());
    }

    public function testNull()
    {
        DB::enableQueryLog();

        Comment::create();

        $this->assertCount(1, DB::getQueryLog());
    }
}

class Comment extends Model
{
    public $timestamps = false;

    protected $touches = ['commentable'];

    public function commentable()
    {
        return $this->morphTo(null, null, null, 'id');
    }
}
