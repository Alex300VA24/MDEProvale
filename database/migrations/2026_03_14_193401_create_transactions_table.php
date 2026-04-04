<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
