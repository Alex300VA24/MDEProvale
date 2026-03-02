<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Transaction::factory()->count(200)->create();
    }
}
