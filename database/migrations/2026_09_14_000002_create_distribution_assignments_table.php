<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL confirma el CREATE TABLE antes de agregar índices. Si una
        // ejecución anterior falló al crear el índice, completar la tabla
        // parcial permite reintentar la migración sin eliminar datos.
        if (Schema::hasTable('distribution_assignments')) {
            Schema::table('distribution_assignments', function (Blueprint $table) {
                $table->index(['distribution_period_id', 'route_number'], 'distribution_period_route_idx');
            });

            return;
        }

        Schema::create('distribution_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('route_number')->default(1);
            $table->integer('beneficiary_adjustment')->default(0);
            $table->string('observation', 250)->nullable();
            $table->timestamps();

            $table->unique(['distribution_period_id', 'association_id'], 'distribution_period_association_unique');
            $table->index(['distribution_period_id', 'route_number'], 'distribution_period_route_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_assignments');
    }
};
