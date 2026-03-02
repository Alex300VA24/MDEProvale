<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    public function definition()
    {
        return [
            'date_begin' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
            'date_end' => $this->faker->dateTimeBetween('+1 year', '+2 years'),
            'observations' => $this->faker->randomElement([null, $this->faker->sentence(), $this->faker->paragraph()]),
            'state_id' => $this->faker->numberBetween(1, 5),
            'person_id' => $this->faker->numberBetween(1, 200),
            'association_id' => $this->faker->numberBetween(1, 10),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
