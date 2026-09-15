<?php

namespace Heritage\Tests\Integration\Database\EloquentMorphToGlobalScopesTest;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\SoftDeletes;
use Heritage\Database\Eloquent\SoftDeletingScope;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Database\Fixtures\Models\Comment;
use Heritage\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphToGlobalScopesTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $post = Post::create();
        (new Comment)->commentable()->associate($post)->save();

        $post = tap(Post::create())->delete();
        (new Comment)->commentable()->associate($post)->save();
    }

    public function testWithGlobalScopes()
    {
        $comments = Comment::with('commentable')->get();

        $this->assertNotNull($comments[0]->commentable);
        $this->assertNull($comments[1]->commentable);
    }

    public function testWithoutGlobalScope()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }])->get();

        $this->assertNotNull($comments[0]->commentable);
        $this->assertNotNull($comments[1]->commentable);
    }

    public function testWithoutGlobalScopes()
    {
        $comments = Comment::with(['commentable' => function ($query) {
            $query->withoutGlobalScopes();
        }])->get();

        $this->assertNotNull($comments[0]->commentable);
        $this->assertNotNull($comments[1]->commentable);
    }

    public function testLazyLoading()
    {
        $comment = Comment::latest('id')->first();
        $post = $comment->commentable()->withoutGlobalScopes()->first();

        $this->assertNotNull($post);
    }
}

class Post extends Model
{
    use SoftDeletes;

    public $timestamps = false;
}
