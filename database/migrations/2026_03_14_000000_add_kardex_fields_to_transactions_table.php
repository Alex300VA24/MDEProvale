<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKardexFieldsToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('document_number', 20)->nullable()->after('type_transaction_id');
            $table->decimal('stock_quantity', 8, 3)->nullable()->after('document_number');
            $table->decimal('stock_unit_price', 8, 2)->nullable()->after('stock_quantity');
            $table->decimal('stock_total_price', 8, 2)->nullable()->after('stock_unit_price');
            $table->decimal('adjustment', 8, 2)->nullable()->after('stock_total_price');
            $table->date('transaction_date')->nullable()->after('adjustment');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'document_number',
                'stock_quantity',
                'stock_unit_price',
                'stock_total_price',
                'adjustment',
                'transaction_date',
            ]);
        });
    }
}