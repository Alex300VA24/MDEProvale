<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToPeopleTable extends Migration
{
    public function up()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->index('names', 'idx_people_names');
            $table->index('father_lastname', 'idx_people_father_lastname');
            $table->index('mother_lastname', 'idx_people_mother_lastname');
            $table->index('dni', 'idx_people_dni');
        });
    }

    public function down()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_names');
            $table->dropIndex('idx_people_father_lastname');
            $table->dropIndex('idx_people_mother_lastname');
            $table->dropIndex('idx_people_dni');
        });
    }
}
