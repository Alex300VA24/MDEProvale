<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ReplaceProductIdWithDetailProductIdInDetailPecosasTable extends Migration
{
    public function up()
    {
        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->unsignedBigInteger('detail_product_id')->nullable()->after('product_id');
        });

        // Intentar poblar detail_product_id desde product_stocks si existe relación
        // (best-effort, puede quedar null si no hay relación directa)
        DB::statement('
            UPDATE dp
            SET dp.detail_product_id = ps.detail_product_id
            FROM detail_pecosas dp
            INNER JOIN product_stocks ps ON ps.pecosa_id = dp.pecosa_id
            WHERE dp.detail_product_id IS NULL
        ');

        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->foreign('detail_product_id')->references('id')->on('detail_products')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->dropForeign(['detail_product_id']);
            $table->dropColumn('detail_product_id');
            $table->foreignId('product_id')->constrained();
        });
    }
}
