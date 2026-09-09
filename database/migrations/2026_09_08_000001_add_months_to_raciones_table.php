<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMonthsToRacionesTable extends Migration
{
    public function up()
    {
        // Paso 1: Agregar columnas month_start y month_end como nullable temporalmente
        Schema::table('raciones', function (Blueprint $table) {
            $table->tinyInteger('month_start')->after('year')->nullable();
            $table->tinyInteger('month_end')->after('month_start')->nullable();
        });

        // Paso 2: Migrar datos existentes: raciones sin mes se convierten a ración anual (1-12)
        DB::table('raciones')->whereNull('month_start')->update([
            'month_start' => 1,
            'month_end' => 12,
        ]);

        // Paso 3: Hacer las columnas NOT NULL y ajustar constraints
        Schema::table('raciones', function (Blueprint $table) {
            $table->tinyInteger('month_start')->nullable(false)->change();
            $table->tinyInteger('month_end')->nullable(false)->change();

            // Eliminar el UNIQUE simple en year (nombre del constraint en Laravel)
            $table->dropIndex('raciones_year_unique');

            // Agregar UNIQUE compuesto para evitar períodos duplicados
            $table->unique(['year', 'month_start', 'month_end']);
        });
    }

    public function down()
    {
        Schema::table('raciones', function (Blueprint $table) {
            $table->dropIndex('raciones_year_month_start_month_end_unique');
            $table->dropColumn(['month_start', 'month_end']);
            $table->unique('year');
        });
    }
}
