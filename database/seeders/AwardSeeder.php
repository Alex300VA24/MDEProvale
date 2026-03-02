<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AwardSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Award::factory()->count(50)->create();
    }
}
