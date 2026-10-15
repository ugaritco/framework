<?php

namespace Heritage\Tests\Integration\Generators;

class PolicyMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Policies/FooPolicy.php',
        'app/Policies/Post/FooPolicy.php',
    ];

    public function testItCanGeneratePolicyFile()
    {
        $this->scribe('make:policy', ['name' => 'FooPolicy'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Policies;',
            'class FooPolicy',
        ], 'app/Policies/FooPolicy.php');

        $this->assertFileNotContains([
            'User',
        ], 'app/Policies/FooPolicy.php');
    }

    public function testItCanGeneratePolicyFileWithModelOption()
    {
        $this->scribe('make:policy', ['name' => 'FooPolicy', '--model' => 'Post'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Policies\Post;',
            'use App\Models\Post;',
            'use Heritage\Contracts\Auth\Actor;',
            'class FooPolicy',
            'public function viewAny(Actor $actor)',
            'public function view(Actor $actor, Post $post)',
            'public function create(Actor $actor)',
            'public function update(Actor $actor, Post $post)',
            'public function save(Actor $actor, ?Post $post = null)',
            'public function delete(Actor $actor, Post $post)',
            'public function restore(Actor $actor, Post $post)',
            'public function forceDelete(Actor $actor, Post $post)',
        ], 'app/Policies/Post/FooPolicy.php');

        $this->assertFileNotContains([
            'User',
        ], 'app/Policies/Post/FooPolicy.php');
    }
}
