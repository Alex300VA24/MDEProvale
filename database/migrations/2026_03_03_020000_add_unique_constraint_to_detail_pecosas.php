<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToDetailPecosas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->unique(['pecosa_id', 'product_id'], 'detail_pecosas_pecosa_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->dropUnique('detail_pecosas_pecosa_product_unique');
        });
    }
}
