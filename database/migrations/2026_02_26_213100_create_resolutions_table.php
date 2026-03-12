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
            $table->dateTime('date_document')->default(DB::raw('GETDATE()'));
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->foreignId('state_id')->constrained();
            $table->timestamps();
        });

        // añadir la columna varbinary(max)
        DB::statement('ALTER TABLE resolutions ADD [file] VARBINARY(MAX) NULL');

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
