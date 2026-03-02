<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObstetricDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obstetric_data', function (Blueprint $table) {
            $table->id();
            $table->date('date_last_menstrual_period')->nullable();
            $table->date('date_estimated_parturition')->nullable();
            $table->date('date_parturition')->nullable();
            $table->date('date_end_breastfeeding')->nullable();
            $table->foreignId('beneficiary_history_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obstetric_data');
    }
}
