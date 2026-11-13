<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resolutions', function (Blueprint $table) {
            // Motivo por el que la resolución se registró como Pendiente.
            $table->text('observation')->nullable()->after('file_path');
        });
        // El estado Pendiente (PEN) ya lo siembra StateSeeder.
    }

    public function down(): void
    {
        Schema::table('resolutions', function (Blueprint $table) {
            $table->dropColumn('observation');
        });
    }
};
