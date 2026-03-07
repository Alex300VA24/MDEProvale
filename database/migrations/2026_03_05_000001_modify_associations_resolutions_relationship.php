<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyAssociationsResolutionsRelationship extends Migration
{
    public function up()
    {
        // 1. Agregar resolution_id a associations (nullable inicialmente)
        Schema::table('associations', function (Blueprint $table) {
            $table->foreignId('resolution_id')->nullable()->constrained()->onDelete('cascade');
        });

        // 2. Copiar los datos existentes: cada association obtiene su primera resolution
        DB::statement('
            UPDATE associations 
            SET resolution_id = (
                SELECT TOP 1 id FROM resolutions 
                WHERE resolutions.association_id = associations.id 
                ORDER BY id
            )
            WHERE EXISTS (
                SELECT 1 FROM resolutions 
                WHERE resolutions.association_id = associations.id
            )
        ');

        // 3. Eliminar la columna association_id de resolutions
        Schema::table('resolutions', function (Blueprint $table) {
            $table->dropForeign(['association_id']);
            $table->dropColumn('association_id');
        });

        // 4. Hacer resolution_id NOT NULL en associations
        Schema::table('associations', function (Blueprint $table) {
            $table->foreignId('resolution_id')->nullable(false)->change();
        });
    }

    public function down()
    {
        // Agregar association_id de vuelta a resolutions
        Schema::table('resolutions', function (Blueprint $table) {
            $table->foreignId('association_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Copiar los datos de vuelta
        DB::statement('
            UPDATE resolutions 
            SET association_id = (
                SELECT TOP 1 associations.id FROM associations 
                WHERE associations.resolution_id = resolutions.id
            )
            WHERE EXISTS (
                SELECT 1 FROM associations 
                WHERE associations.resolution_id = resolutions.id
            )
        ');

        // Eliminar resolution_id de associations
        Schema::table('associations', function (Blueprint $table) {
            $table->dropForeign(['resolution_id']);
            $table->dropColumn('resolution_id');
        });
    }
}
