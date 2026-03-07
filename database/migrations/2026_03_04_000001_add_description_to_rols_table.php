<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToRolsTable extends Migration
{
    public function up()
    {
        Schema::table('rols', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('title');
        });
    }

    public function down()
    {
        Schema::table('rols', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}
