<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeopleSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/people.json');
        if (!is_file($path)) {
            throw new RuntimeException("No se encontró {$path}");
        }

        $sourceRows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $validPlaceSectorIds = DB::table('place_sectors')->pluck('id')->flip();
        $sourcePeople = [];

        foreach ($sourceRows as $row) {
            $dni = $this->normalizeDni($row[3] ?? null);
            if (!$dni) {
                continue;
            }

            $placeSectorId = isset($row[9]) ? (int) $row[9] : null;
            if (!$placeSectorId || !$validPlaceSectorIds->has($placeSectorId)) {
                $placeSectorId = null;
            }

            // La última aparición de una persona contiene sus correcciones más
            // recientes dentro del padrón marzo-setiembre 2026.
            $sourcePeople[$dni] = [
                'names' => $row[0] ?? '',
                'father_lastname' => $row[1] ?? '',
                'mother_lastname' => $row[2] ?? '',
                'dni' => $dni,
                'gender' => $row[4] ?? null,
                'telephone_number' => $row[5] ?? null,
                'phone_number' => $row[6] ?? null,
                'birthdate' => $this->normalizeBirthdate($row[7] ?? null),
                'address' => $row[8] ?? null,
                'place_sector_id' => $placeSectorId,
                'created_at' => $row[10] ?? now(),
                'updated_at' => now(),
            ];
        }

        $existingPeople = DB::table('people')->get(['id', 'dni'])->keyBy('dni');
        $updates = [];
        $inserts = [];

        foreach ($sourcePeople as $dni => $person) {
            $existing = $existingPeople->get($dni);
            if ($existing) {
                $updates[] = array_merge(['id' => $existing->id], $person);
            } else {
                $inserts[] = $person;
            }
        }

        $columns = [
            'names', 'father_lastname', 'mother_lastname', 'dni', 'gender',
            'telephone_number', 'phone_number', 'birthdate', 'address',
            'place_sector_id', 'created_at', 'updated_at',
        ];

        foreach (array_chunk($updates, 500) as $chunk) {
            DB::table('people')->upsert($chunk, ['id'], $columns);
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('people')->insert($chunk);
        }

        // Se conservan únicamente las personas del padrón y los funcionarios
        // requeridos por responsibles; socios y beneficiarios ya fueron limpiados.
        $responsiblePersonIds = DB::table('responsibles')->pluck('person_id')->all();
        DB::table('people')
            ->whereNotIn('dni', array_keys($sourcePeople))
            ->when($responsiblePersonIds, fn ($query) => $query->whereNotIn('id', $responsiblePersonIds))
            ->delete();

        $this->command->info('Personas del padrón sincronizadas: ' . count($sourcePeople));
    }

    private function normalizeDni($value): ?string
    {
        $dni = trim((string) $value);
        if ($dni === '') {
            return null;
        }

        return $dni === '178993805' ? '17899385' : $dni;
    }

    private function normalizeBirthdate($value): ?string
    {
        $date = trim((string) $value);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        $date = str_replace('//', '/', $date);
        if (preg_match('/^(\d{2})\/(\d{2})\/?(\d{4})$/', $date, $parts)) {
            $day = (int) $parts[1];
            $month = (int) $parts[2];
            $year = (int) $parts[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }
}
