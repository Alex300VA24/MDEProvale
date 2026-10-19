<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('positions')) {
            return;
        }

        $titles = [
            'PRESIDENTA',
            'VICE PRESIDENTA',
            'SECRETARIA',
            'TESORERA',
            'COORDINADORA',
            'ALMACENERA',
            'VOCAL',
        ];

        foreach ($titles as $title) {
            $existing = DB::table('positions')
                ->whereRaw('UPPER(title) = ?', [$title])
                ->first();

            if ($existing) {
                DB::table('positions')->where('id', $existing->id)->update([
                    'title' => $title,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('positions')->insert([
                'title' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Los cargos pueden tener directivas asociadas; no se elimina su historial.
    }
};
