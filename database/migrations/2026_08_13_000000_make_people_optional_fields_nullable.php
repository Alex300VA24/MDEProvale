<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las reglas de validación de Personas (Store/UpdatePersonaRequest) marcan
 * gender, birthdate, address y place_sector_id como opcionales (nullable),
 * pero la tabla los creó como NOT NULL sin default: guardar una persona sin
 * completar esos campos rompía con un SQLSTATE crudo en vez del mensaje de
 * validación esperado. Se relajan a nullable para que coincidan con las reglas.
 */
class MakePeopleOptionalFieldsNullable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE people MODIFY gender CHAR(1) NULL');
        DB::statement('ALTER TABLE people MODIFY birthdate DATE NULL');
        DB::statement('ALTER TABLE people MODIFY address VARCHAR(200) NULL');
        DB::statement('ALTER TABLE people MODIFY place_sector_id BIGINT UNSIGNED NULL');
    }

    public function down()
    {
        DB::statement("UPDATE people SET gender = 'M' WHERE gender IS NULL");
        DB::statement('UPDATE people SET birthdate = CURDATE() WHERE birthdate IS NULL');
        DB::statement("UPDATE people SET address = '' WHERE address IS NULL");

        DB::statement('ALTER TABLE people MODIFY gender CHAR(1) NOT NULL');
        DB::statement('ALTER TABLE people MODIFY birthdate DATE NOT NULL');
        DB::statement('ALTER TABLE people MODIFY address VARCHAR(200) NOT NULL');
    }
}
