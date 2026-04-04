<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('directives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained();
            $table->foreignId('partner_id')->constrained();
            $table->foreignId('position_id')->constrained();
            $table->foreignId('state_id')->constrained();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('directives');
    }
}
