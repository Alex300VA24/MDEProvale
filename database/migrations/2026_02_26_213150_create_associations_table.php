<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssociationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('address', 150);
            $table->string('company_name', 10);
            $table->text('observation')->nullable();
            $table->string('president', 150)->nullable();
            $table->foreignId('resolution_id')->constrained();
            $table->foreignId('state_id')->constrained();
            $table->foreignId('place_sector_id')->constrained();
            $table->foreignId('type_premises_id')->constrained();
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
        Schema::dropIfExists('associations');
    }
}
