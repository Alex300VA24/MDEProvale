<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PecosaSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Pecosa::factory()->count(100)->create();
    }
}
