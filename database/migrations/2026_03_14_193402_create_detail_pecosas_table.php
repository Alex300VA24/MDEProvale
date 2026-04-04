<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailPecosasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_pecosas', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('quantity');
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('subtotal', 10, 2);
            $table->foreignId('detail_product_id')->constrained();
            $table->foreignId('pecosa_id')->constrained();
            
            // Snapshot histórico del producto al momento de la pecosa
            $table->string('product_name', 100)->nullable();
            $table->string('product_abbreviation', 5)->nullable();
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
        Schema::dropIfExists('detail_pecosas');
    }
}
