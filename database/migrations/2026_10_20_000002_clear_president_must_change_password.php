<?php

use App\Models\Rol;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El cambio de contraseña del portal de presidentas es opcional: se libera
     * a las cuentas que quedaron marcadas con cambio obligatorio.
     */
    public function up(): void
    {
        $roleIds = DB::table('rols')->where('title', Rol::PRESIDENT)->pluck('id');

        DB::table('users')
            ->whereIn('rol_id', $roleIds)
            ->where('must_change_password', true)
            ->update(['must_change_password' => false]);
    }

    public function down(): void
    {
        // Sin reversión: el estado previo no se conserva.
    }
};
