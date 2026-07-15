<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateResolutionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('document', 100);
            $table->dateTime('date_document')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->string('file_path', 255)->nullable(); // ruta del archivo en storage
            $table->foreignId('state_id')->constrained();
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
        Schema::dropIfExists('resolutions');
    }
}
