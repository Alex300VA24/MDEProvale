<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tabla associations no tenía columna phone: el formulario de Comités
     * (Blade y React) la envía al actualizar, pero el INSERT/UPDATE fallaba.
     */
    public function up(): void
    {
        Schema::table('associations', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('associations', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
