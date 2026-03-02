<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AwardFactory extends Factory
{
    public function definition()
    {
        return [
            'document' => 'DOC-' . $this->faker->numerify('#######'),
            'date_document' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
            'date_start' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'date_end' => $this->faker->dateTimeBetween('+1 year', '+2 years'),
            'association_id' => $this->faker->numberBetween(1, 10),
            'state_id' => $this->faker->numberBetween(1, 5),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
