<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PecosaResponsableSeeder (generado por migracion_responsables/mapear_pecosas.py)
 * ----------------------------------------------------------------------------
 * Actualiza pecosas ya migradas con los responsables que firmaron cada una:
 *   - chief       = subgerenta de programas sociales (PECOSA.JEF_id).
 *   - storekeeper = encargada de PROVALE (PECOSA.ALM_id).
 *
 * Los responsables se resuelven en runtime por DNI contra responsibles/people.
 * Las pecosas del origen sin responsable quedan sin tocar (chief_id/
 * storekeeper_id siguen null).
 *
 * Pecosas a actualizar: 2802 (de data/pecosas_responsables.json).
 */
class PecosaResponsableSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = __DIR__ . '/data/pecosas_responsables.json';

        if (! is_file($ruta)) {
            throw new \RuntimeException("No se encontro {$ruta}. Copia la carpeta data/ junto a los seeders.");
        }

        $filas = json_decode(file_get_contents($ruta), true);
        if (! is_array($filas)) {
            throw new \RuntimeException("El archivo {$ruta} no tiene un JSON valido.");
        }

        // Responsables por rol, resueltos por DNI de la persona.
        $responsable = [];
        $persona = [];
        foreach (['chief', 'storekeeper'] as $rol) {
            $rows = DB::table('responsibles')
                ->join('people', 'people.id', '=', 'responsibles.person_id')
                ->where('responsibles.type', $rol)
                ->select('responsibles.id', 'people.dni')
                ->get();
            foreach ($rows as $r) {
                $responsable[$rol][$r->dni] = $r->id;
                $persona[$r->dni] = DB::table('people')->where('dni', $r->dni)->first();
            }
        }

        $ahora = now();
        foreach ($filas as $fila) {
            [$numero, $dniJef, $dniAlm] = $fila;

            $updates = [];
            foreach ([
                'chief'       => [$dniJef, 'chief_id', 'chief_dni', 'chief_name'],
                'storekeeper' => [$dniAlm, 'storekeeper_id', 'storekeeper_dni', 'storekeeper_name'],
            ] as $rol => [$dni, $idCol, $dniCol, $nameCol]) {
                if ($dni !== null && isset($responsable[$rol][$dni])) {
                    $updates[$idCol] = $responsable[$rol][$dni];
                    $updates[$dniCol] = $dni;
                    $updates[$nameCol] = trim(implode(' ', array_filter([
                        $persona[$dni]->names,
                        $persona[$dni]->father_lastname,
                        $persona[$dni]->mother_lastname,
                    ])));
                }
            }

            if (! $updates) {
                continue;
            }
            $updates['updated_at'] = $ahora;

            DB::table('pecosas')->where('pecosa_number', $numero)->update($updates);
        }
    }
}
