<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionIdToProductStocksTable extends Migration
{
    public function up()
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->foreignId('transaction_id')
                ->nullable()
                ->after('pecosa_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_id');
        });
    }
}
