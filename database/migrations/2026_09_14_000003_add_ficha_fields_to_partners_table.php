<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('marital_status', 50)->nullable()->after('observations');
            $table->string('education_level', 50)->nullable()->after('marital_status');
            $table->string('occupation', 100)->nullable()->after('education_level');
            $table->unsignedInteger('children_count')->nullable()->after('occupation');
            $table->boolean('is_pregnant')->default(false)->after('children_count');
            $table->boolean('is_lactating')->default(false)->after('is_pregnant');
            $table->string('spouse_occupation', 100)->nullable()->after('is_lactating');
            $table->string('spouse_education_level', 50)->nullable()->after('spouse_occupation');
            $table->decimal('family_income', 10, 2)->nullable()->after('spouse_education_level');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'marital_status',
                'education_level',
                'occupation',
                'children_count',
                'is_pregnant',
                'is_lactating',
                'spouse_occupation',
                'spouse_education_level',
                'family_income',
            ]);
        });
    }
};
