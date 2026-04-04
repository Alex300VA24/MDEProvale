<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssociationsTable extends Migration
{
    public function up()
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('address', 150);
            $table->string('company_name', 150);
            $table->text('observation')->nullable();
            $table->foreignId('resolution_id')->constrained();
            $table->foreignId('state_id')->constrained();
            $table->foreignId('place_sector_id')->constrained();
            $table->foreignId('type_premises_id')->constrained();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('associations');
    }
}