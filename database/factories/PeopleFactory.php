<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PeopleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'names' => $this->faker->name(),
            'father_lastname' => $this->faker->lastName(),
            'mother_lastname' => $this->faker->lastName(),
            'dni' => $this->faker->numerify('########'), // Debe tener solo 8 numeros
            'gender' => $this->faker->randomElement(['M', 'F']),
            'telephone_number' => $this->faker->numerify('######'), // Deben ser 6 numeros,
            'phone_number' => $this->faker->numerify('#########'), // Deben ser 9 numeros,
            'birthdate' => $this->faker->date(),
            'years_old' => $this->faker->numberBetween(1, 100),
            'months_old' => $this->faker->numberBetween(1, 12),
            'days_old' => $this->faker->numberBetween(1, 31),
            'address' => $this->faker->address(),
            'finca_number' => $this->faker->numberBetween(1, 1000),
            'place_sector_id' => $this->faker->numberBetween(1, 5),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }
}
