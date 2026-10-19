<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribution_assignments', function (Blueprint $table) {
            $table->decimal('scheduled_milk', 14, 2)->nullable()->after('beneficiary_adjustment');
            $table->decimal('scheduled_milk_rounded', 14, 2)->nullable()->after('scheduled_milk');
            $table->decimal('scheduled_oat', 14, 2)->nullable()->after('scheduled_milk_rounded');
            $table->decimal('scheduled_oat_rounded', 14, 2)->nullable()->after('scheduled_oat');
        });
    }

    public function down(): void
    {
        Schema::table('distribution_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_milk',
                'scheduled_milk_rounded',
                'scheduled_oat',
                'scheduled_oat_rounded',
            ]);
        });
    }
};
