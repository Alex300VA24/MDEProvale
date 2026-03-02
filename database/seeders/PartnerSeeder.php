<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Partner::factory()->count(500)->create();
    }
}
