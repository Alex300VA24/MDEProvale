<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodeToProductsTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('products', 'code')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('abbreviation');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('products', 'code')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
}
