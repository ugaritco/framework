<?php

namespace Heritage\Tests\Integration\Generators;

class PolicyMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Policies/FooPolicy.php',
    ];

    public function testItCanGeneratePolicyFile()
    {
        $this->scribe('make:policy', ['name' => 'FooPolicy'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Policies;',
            'use Heritage\Foundation\Auth\User;',
            'class FooPolicy',
        ], 'app/Policies/FooPolicy.php');
    }

    public function testItCanGeneratePolicyFileWithModelOption()
    {
        $this->scribe('make:policy', ['name' => 'FooPolicy', '--model' => 'Post'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Policies;',
            'use App\Models\Post;',
            'use Heritage\Foundation\Auth\User;',
            'class FooPolicy',
            'public function viewAny(User $user)',
            'public function view(User $user, Post $post)',
            'public function create(User $user)',
            'public function update(User $user, Post $post)',
            'public function delete(User $user, Post $post)',
            'public function restore(User $user, Post $post)',
            'public function forceDelete(User $user, Post $post)',
        ], 'app/Policies/FooPolicy.php');
    }
}
