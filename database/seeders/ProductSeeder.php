<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $path = __DIR__ . '/data/products.json';
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontro {$path}. Ejecuta migracion_productos/creando_seeders.py.");
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            throw new \RuntimeException("El archivo {$path} no contiene JSON valido.");
        }

        $activeStateId = State::idFor(State::ACTIVE);
        if (! $activeStateId) {
            throw new \RuntimeException('No se encontro el estado Activo para sembrar los productos.');
        }

        $now = now();
        foreach ($rows as $row) {
            DB::table('products')->updateOrInsert(
                ['title' => $row['title']],
                [
                    'abbreviation' => $row['abbreviation'],
                    'state_id' => $activeStateId,
                    'uom_id' => $row['uom_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Productos insertados: ' . count($rows));
    }
}
