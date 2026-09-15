<?php

namespace Heritage\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Heritage\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\Factories\UserFactory;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
        ];
    }

    #[\Override]
    public function modelName()
    {
        return Profile::class;
    }
}
