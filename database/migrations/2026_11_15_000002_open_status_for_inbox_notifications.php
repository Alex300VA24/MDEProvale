<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Los avisos informativos pasan a seguimiento: cuentan hasta marcarse como subsanados. */
    public function up(): void
    {
        DB::table('notifications')
            ->whereIn('type', ['normativa_pvl', 'alerta_beneficiarios'])
            ->where('status', 'informativo')
            ->update(['status' => 'por_solucionar']);
    }

    public function down(): void
    {
        DB::table('notifications')
            ->whereIn('type', ['normativa_pvl', 'alerta_beneficiarios'])
            ->where('status', 'por_solucionar')
            ->update(['status' => 'informativo']);
    }
};
