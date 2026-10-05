<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kb_documents', function (Blueprint $table) {
            $table->longText('file_data')->nullable()->change();
        });

        Schema::table('pvl_documents', function (Blueprint $table) {
            $table->longText('file_data')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('kb_documents')->whereNull('file_data')->update(['file_data' => '']);
        DB::table('pvl_documents')->whereNull('file_data')->update(['file_data' => '']);

        Schema::table('kb_documents', function (Blueprint $table) {
            $table->longText('file_data')->nullable(false)->change();
        });

        Schema::table('pvl_documents', function (Blueprint $table) {
            $table->longText('file_data')->nullable(false)->change();
        });
    }
};
