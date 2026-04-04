<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePecosasTable extends Migration
{
    public function up()
    {
        Schema::create('pecosas', function (Blueprint $table) {
            $table->id();
            $table->char('pecosa_number', 8)->unique();
            $table->text('observation')->nullable();
            $table->dateTime('delivery_date');
            $table->unsignedBigInteger('chief_id');
            $table->unsignedBigInteger('storekeeper_id');
            $table->unsignedBigInteger('managing_partner_id')->nullable();
            $table->unsignedBigInteger('president_id')->nullable();
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('association_id');
            
            // Campos históricos - responsables
            $table->string('chief_name', 150)->nullable();
            $table->string('storekeeper_name', 150)->nullable();
            $table->string('managing_partner_name', 150)->nullable();
            $table->string('president_name', 150)->nullable();
            
            // Campos históricos - asociación
            $table->string('association_name', 100)->nullable();
            $table->string('association_code', 20)->nullable();
            
            $table->timestamps();
            
            $table->foreign('chief_id')->references('id')->on('responsibles');
            $table->foreign('storekeeper_id')->references('id')->on('responsibles');
            $table->foreign('managing_partner_id')->references('id')->on('partners');
            $table->foreign('president_id')->references('id')->on('partners');
            $table->foreign('state_id')->references('id')->on('states');
            $table->foreign('association_id')->references('id')->on('associations');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pecosas');
    }
}