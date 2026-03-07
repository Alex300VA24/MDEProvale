<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ResolutionSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Resolution::factory()->count(50)->create();
    }
}
