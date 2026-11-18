<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('distribution_assignments') && ! Schema::hasColumn('distribution_assignments', 'included')) {
            Schema::table('distribution_assignments', function (Blueprint $table) {
                // null = por defecto (solo comités activos); true/false = decisión del usuario.
                $table->boolean('included')->nullable()->after('beneficiary_adjustment');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('distribution_assignments', 'included')) {
            Schema::table('distribution_assignments', function (Blueprint $table) {
                $table->dropColumn('included');
            });
        }
    }
};
