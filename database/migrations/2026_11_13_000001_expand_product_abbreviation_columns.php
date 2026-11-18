<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('abbreviation', 20)->nullable()->change();
        });

        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->string('product_abbreviation', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->char('abbreviation', 5)->nullable()->change();
        });

        Schema::table('detail_pecosas', function (Blueprint $table) {
            $table->string('product_abbreviation', 5)->nullable()->change();
        });
    }
};
