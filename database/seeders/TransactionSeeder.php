<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    public function run()
    /**
     * Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_price', 8, 2);
            $table->string('document_number', 20)->nullable();
            $table->decimal('adjustment', 8, 2)->nullable();
            $table->date('transaction_date')->nullable()->after('adjustment');
            $table->foreignId('detail_product_id')->constrained();
            $table->foreignId('type_transaction_id')->constrained();
            
            // Snapshot histórico del producto al momento de la transacción
            $table->string('product_name', 100)->nullable();
            $table->string('uom_title', 80)->nullable();
            $table->timestamps();
        });
     */
    {
        //\App\Models\Transaction::factory()->count(200)->create();
        DB::table('transactions')->insert([
            "quantity" => 12882,
            "unit_price" => 3.82,
            "total_price" => 49209.24,
            "detail_product_id" => 1,
            "type_transaction_id" => 1,
            "document_number" => "0180-2026-MDE",
            "adjustment" => null,
            "transaction_date" => "2026-03-24",
            "product_name" => "Producto 1",
            "uom_title" => "Unidad",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        DB::table('transactions')->insert([
            "quantity" => 5983,
            "unit_price" => 7.8,
            "total_price" => 427767.6,
            "detail_product_id" => 2,
            "type_transaction_id" => 1,
            "document_number" => "0175-2026-MDE",
            "adjustment" => null,
            "transaction_date" => "2026-03-24",
            "product_name" => "Producto 2",
            "uom_title" => "Unidad",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
    }
}
