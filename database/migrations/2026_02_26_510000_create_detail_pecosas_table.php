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
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->foreignId('product_id')->constrained();
            $table->foreignId('pecosa_id')->constrained();
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
