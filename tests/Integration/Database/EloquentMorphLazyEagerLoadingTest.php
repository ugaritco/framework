<?php

namespace Heritage\Tests\Integration\Database\EloquentMorphLazyEagerLoadingTest;

use Heritage\Database\Eloquent\Model;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\Schema;
use Heritage\Tests\Database\Fixtures\Models\Comment;
use Heritage\Tests\Database\Fixtures\Models\MorphEagerLoading\User;
use Heritage\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphLazyEagerLoadingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $user = User::create();

        $post = tap((new Post)->user()->associate($user))->save();

        (new Comment)->commentable()->associate($post)->save();
    }

    public function testLazyEagerLoading()
    {
        $comment = Comment::first();

        $comment->loadMorph('commentable', [
            Post::class => ['user'],
        ]);

        $this->assertTrue($comment->relationLoaded('commentable'));
        $this->assertTrue($comment->commentable->relationLoaded('user'));
    }
}

class Post extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'post_id';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
