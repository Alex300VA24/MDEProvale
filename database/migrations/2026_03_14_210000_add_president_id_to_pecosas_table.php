<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPresidentIdToPecosasTable extends Migration
{
    public function up()
    {
        Schema::table('pecosas', function (Blueprint $table) {
            $table->foreignId('president_id')->nullable()->constrained('partners')->after('managing_partner_id');
        });
    }

    public function down()
    {
        Schema::table('pecosas', function (Blueprint $table) {
            $table->dropColumn('president_id');
        });
    }
}