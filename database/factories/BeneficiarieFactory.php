<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BeneficiarieFactory extends Factory
{
    public function definition()
    {
        return [
            'person_id' => $this->faker->numberBetween(1, 200),
            'partner_id' => $this->faker->numberBetween(1, 500),
            'relationship_id' => $this->faker->numberBetween(1, 4),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
