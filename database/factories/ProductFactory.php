<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition()
    {
        $products = [
            'Leche Evaporada', 'Leche Condensada', 'Avena', 'Fideos', 'Arroz',
            'Azúcar', 'Aceite', 'Manteca', 'Conservas de Pescado', 'Huevos',
            'Menestra', 'Harina', 'Sal', 'Salsa de Soya', 'Vinagre',
        ];

        return [
            'title' => $this->faker->randomElement($products) . ' ' . $this->faker->randomElement(['Premium', 'Economico', 'Extra', '']),
            'abbreviation' => strtoupper(substr($this->faker->word(), 0, 3)),
            'stock' => $this->faker->numberBetween(10, 500),
            'unit_price' => $this->faker->randomFloat(2, 1, 50),
            'state_id' => $this->faker->numberBetween(1, 5),
            'uom_id' => $this->faker->numberBetween(1, 2),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
