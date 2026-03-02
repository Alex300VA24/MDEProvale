<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PeopleSeeder extends Seeder
{
    public function run()
    {
        \App\Models\People::factory()->count(200)->create();
    }
}
