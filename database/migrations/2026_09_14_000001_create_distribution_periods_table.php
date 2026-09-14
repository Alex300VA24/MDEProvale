<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('service_days');
            $table->decimal('milk_grams_per_beneficiary', 8, 3)->default(44.000);
            $table->decimal('oat_grams_per_beneficiary', 8, 3)->default(51.500);
            $table->decimal('milk_can_grams', 8, 3)->default(410.000);
            $table->decimal('oat_bag_grams', 8, 3)->default(1000.000);
            $table->unsignedSmallInteger('milk_cans_per_box')->default(48);
            $table->unsignedSmallInteger('oat_kg_per_sack')->default(30);
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_periods');
    }
};
