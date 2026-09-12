<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_roster_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->date('period');
            $table->unsignedInteger('partner_count')->default(0);
            $table->unsignedInteger('beneficiary_count')->default(0);
            $table->string('president_name', 200)->nullable();
            $table->foreignId('president_partner_id')
                ->nullable()
                ->constrained('partners')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['association_id', 'period'], 'association_roster_period_unique');
            $table->index('period');
        });

        Schema::create('partner_roster_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->date('period');
            $table->timestamps();

            $table->unique(['partner_id', 'period'], 'partner_roster_period_unique');
            $table->index('period');
        });

        Schema::table('beneficiary_histories', function (Blueprint $table) {
            $table->foreignId('relationship_id')
                ->nullable()
                ->after('type_benefit_id')
                ->constrained('relationships');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('relationship_id');
        });

        Schema::dropIfExists('partner_roster_periods');
        Schema::dropIfExists('association_roster_periods');
    }
};
