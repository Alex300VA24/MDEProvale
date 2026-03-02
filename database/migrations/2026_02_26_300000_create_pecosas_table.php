<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePecosasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pecosas', function (Blueprint $table) {
            $table->id();
            $table->char('pecosa_number', 8);
            $table->text('observation')->nullable();
            $table->dateTime('delivery_date');
            $table->integer('managing_partner_id');
            $table->foreignId('state_id')->constrained();
            $table->foreignId('association_id')->constrained();
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
        Schema::dropIfExists('pecosas');
    }
}
