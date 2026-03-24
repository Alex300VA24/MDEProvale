<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBeneficiaryHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beneficiary_histories', function (Blueprint $table) {
            $table->id();
            $table->decimal('weight', 5, 2);
            $table->decimal('height', 5, 2);
            $table->decimal('hmg', 5, 2);
            $table->date('date_begin');
            $table->date('date_end')->nullable();
            $table->foreignId('type_benefit_id')->constrained();
            $table->foreignId('beneficiary_id')->constrained();
            $table->foreignId('state_id')->constrained();
            $table->foreignId('reason_disqualification_id')->nullable()->constrained();
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
        Schema::dropIfExists('beneficiary_histories');
    }
}
