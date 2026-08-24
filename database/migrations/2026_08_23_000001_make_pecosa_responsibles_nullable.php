<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakePecosaResponsiblesNullable extends Migration
{
    public function up()
    {
        Schema::table('pecosas', function (Blueprint $table) {
            $table->dropForeign(['chief_id']);
            $table->dropForeign(['storekeeper_id']);
        });

        DB::statement(
            'ALTER TABLE pecosas ' .
            'MODIFY chief_id BIGINT UNSIGNED NULL, ' .
            'MODIFY storekeeper_id BIGINT UNSIGNED NULL'
        );

        Schema::table('pecosas', function (Blueprint $table) {
            $table->foreign('chief_id')->references('id')->on('responsibles');
            $table->foreign('storekeeper_id')->references('id')->on('responsibles');
        });
    }

    public function down()
    {
        DB::statement('DELETE FROM pecosas WHERE chief_id IS NULL OR storekeeper_id IS NULL');

        Schema::table('pecosas', function (Blueprint $table) {
            $table->dropForeign(['chief_id']);
            $table->dropForeign(['storekeeper_id']);
        });

        DB::statement(
            'ALTER TABLE pecosas ' .
            'MODIFY chief_id BIGINT UNSIGNED NOT NULL, ' .
            'MODIFY storekeeper_id BIGINT UNSIGNED NOT NULL'
        );

        Schema::table('pecosas', function (Blueprint $table) {
            $table->foreign('chief_id')->references('id')->on('responsibles');
            $table->foreign('storekeeper_id')->references('id')->on('responsibles');
        });
    }
}
