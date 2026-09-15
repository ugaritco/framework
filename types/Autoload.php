<?php

use Heritage\Database\Eloquent\Factories\Factory;
use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Database\Eloquent\MassPrunable;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\SoftDeletes;
use Heritage\Foundation\Auth\User as Authenticatable;
use Heritage\Notifications\HasDatabaseNotifications;

class User extends Authenticatable
{
    use HasDatabaseNotifications;
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use MassPrunable;
    use SoftDeletes;

    protected static string $factory = UserFactory::class;
}

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [];
    }
}

class Post extends Model
{
}

enum UserType
{
}
