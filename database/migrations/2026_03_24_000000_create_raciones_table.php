<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRacionesTable extends Migration
{
    public function up()
    {
        Schema::create('raciones', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->decimal('racion_hojuelas_gramos', 8, 2);
            $table->decimal('racion_leche_militros', 8, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique('year');
        });
    }

    public function down()
    {
        Schema::dropIfExists('raciones');
    }
}
