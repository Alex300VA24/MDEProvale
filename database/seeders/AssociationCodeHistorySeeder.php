<?php

namespace Database\Seeders;

use App\Support\AssociationIdentity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssociationCodeHistorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'internal_code' => AssociationIdentity::SOL_Y_ARENA_INTERNAL_CODE,
                'official_code' => '019',
                'valid_from' => '2024-06-28',
                'valid_to' => '2026-08-31',
                'source' => 'PADRON',
                'observation' => 'SOL Y ARENA DE LAS PALMERITAS; código 019 antes de su reutilización.',
            ],
            [
                'internal_code' => AssociationIdentity::ISABEL_CRUZ_CODE,
                'official_code' => '019',
                'valid_from' => AssociationIdentity::ISABEL_START,
                'valid_to' => null,
                'source' => 'PADRON',
                'observation' => 'ISABEL CRUZ DE MARCELO; el padrón reutiliza el código 019 desde setiembre de 2026.',
            ],
        ];

        foreach ($rows as $row) {
            $associationId = DB::table('associations')->where('code', $row['internal_code'])->value('id');
            if (!$associationId) {
                throw new \RuntimeException("No existe la asociación {$row['internal_code']} para registrar su código histórico.");
            }

            DB::table('association_code_histories')->updateOrInsert(
                ['association_id' => $associationId, 'valid_from' => $row['valid_from']],
                [
                    'code' => $row['official_code'],
                    'valid_to' => $row['valid_to'],
                    'source' => $row['source'],
                    'observation' => $row['observation'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
