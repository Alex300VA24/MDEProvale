<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BeneficiarieSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Beneficiarie::factory()->count(300)->create();
    }
}
