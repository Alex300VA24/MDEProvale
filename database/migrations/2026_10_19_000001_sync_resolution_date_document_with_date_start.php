<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("UPDATE resolutions SET date_document = date_start WHERE date_start IS NOT NULL AND DATE(date_document) = '2026-10-06'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
