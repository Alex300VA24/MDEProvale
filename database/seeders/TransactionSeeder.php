<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        //\App\Models\Transaction::factory()->count(200)->create();
        DB::table('transactions')->insert([
            "quantity" => 12882,
            "unit_price" => 3.82,
            "total_price" => 49209.24,
            "product_id" => 1,
            "type_transaction_id" => 1,
            "document_number" => "0180-2026-MDE",
            "adjustment" => null,
            "transaction_date" => "2026-03-24",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        DB::table('transactions')->insert([
            "quantity" => 5983,
            "unit_price" => 7.8,
            "total_price" => 427767.6,
            "product_id" => 2,
            "type_transaction_id" => 1,
            "document_number" => "0175-2026-MDE",
            "adjustment" => null,
            "transaction_date" => "2026-03-24",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
    }
}
