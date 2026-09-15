<?php

namespace Heritage\Tests\Database\Fixtures\Factories\Money;

use Heritage\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
