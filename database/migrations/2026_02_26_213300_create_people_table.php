<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('names', 150);
            $table->string('father_lastname', 150);
            $table->string('mother_lastname', 150);
            $table->char('dni', 8);
            $table->char('gender', 1);
            $table->string('telephone_number', 6)->nullable();
            $table->string('phone_number', 9)->nullable();
            $table->date('birthdate');
            $table->integer('years_old');
            $table->integer('months_old');
            $table->integer('days_old');
            $table->string('address', 200);
            $table->foreignId('place_sector_id')->constrained();
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
        Schema::dropIfExists('people');
    }
}
