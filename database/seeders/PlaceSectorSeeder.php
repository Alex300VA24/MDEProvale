<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlaceSectorSeeder extends Seeder
{
    /**
     * Conserva los IDs historicos 1-77 usados por people.json y los comites,
     * pero resuelve las FK reales aunque los autoincrementos hayan cambiado.
     */
    public function run(): void
    {
        $placeIds = DB::table('places')->pluck('id', 'code');
        $sectorIds = DB::table('sectors')->orderBy('id')->pluck('id')->values();

        if ($placeIds->count() !== 10 || $sectorIds->count() !== 77) {
            throw new RuntimeException('Se esperaban 10 zonas y 77 sectores antes de vincularlos.');
        }

        $now = now();
        foreach ($sectorIds as $index => $sectorId) {
            $legacyId = $index + 1;
            $placeCode = $this->placeCodeFor($legacyId);
            $placeId = $placeIds->get($placeCode);

            if (! $placeId) {
                throw new RuntimeException("No se encontro la zona {$placeCode}.");
            }

            DB::table('place_sectors')->updateOrInsert(
                ['id' => $legacyId],
                [
                    'place_id' => $placeId,
                    'sector_id' => $sectorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function placeCodeFor(int $legacySectorId): string
    {
        return match (true) {
            $legacySectorId <= 16 => '01',
            $legacySectorId <= 38 => '02',
            $legacySectorId <= 49 => '03',
            $legacySectorId <= 55 => '04',
            $legacySectorId <= 57 => '05',
            $legacySectorId <= 68 => '06',
            $legacySectorId <= 70 => '07',
            $legacySectorId === 71 => '08',
            $legacySectorId <= 75 => '09',
            default => '10',
        };
    }
}
