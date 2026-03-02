<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Product::factory()->count(30)->create();
    }
}
