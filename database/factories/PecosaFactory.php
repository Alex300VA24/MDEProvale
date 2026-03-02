<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PecosaFactory extends Factory
{
    public function definition()
    {
        return [
            'pecosa_number' => 'PEC' . $this->faker->numerify('####'),
            'observation' => $this->faker->optional()->sentence(),
            'delivery_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'managing_partner_id' => $this->faker->numberBetween(1, 500),
            'state_id' => $this->faker->numberBetween(1, 5),
            'association_id' => $this->faker->numberBetween(1, 10),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
