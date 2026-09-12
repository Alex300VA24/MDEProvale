<?php

namespace Tests\Feature;

use App\Repositories\PartnerRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartnerRepositoryPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_partners_active_during_any_part_of_the_requested_month(): void
    {
        $now = now();

        DB::table('states')->insert([
            'id' => 1,
            'title' => 'Activo',
            'abbreviation' => 'A',
        ]);
        DB::table('places')->insert([
            'id' => 1,
            'code' => 'Z001',
            'title' => 'Zona de prueba',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('sectors')->insert([
            'id' => 1,
            'title' => 'Sector de prueba',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('place_sectors')->insert([
            'id' => 1,
            'place_id' => 1,
            'sector_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('type_premises')->insert([
            'id' => 1,
            'title' => 'Local de prueba',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('resolutions')->insert([
            'id' => 1,
            'document' => 'RES-TEST',
            'state_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('associations')->insert([
            'id' => 1,
            'code' => 'TEST',
            'name' => 'Comité de prueba',
            'address' => 'Dirección de prueba',
            'company_name' => 'Comité de prueba',
            'resolution_id' => 1,
            'state_id' => 1,
            'place_sector_id' => 1,
            'type_premises_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $periods = [
            ['2026-08-01', '2026-09-15'],
            ['2026-09-20', '2026-10-15'],
            ['2026-08-01', '2026-08-31'],
            ['2026-10-01', null],
        ];

        foreach ($periods as $index => [$dateBegin, $dateEnd]) {
            $personId = DB::table('people')->insertGetId([
                'names' => "Persona {$index}",
                'father_lastname' => 'Prueba',
                'mother_lastname' => 'Mensual',
                'dni' => str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
                'gender' => 'F',
                'birthdate' => '1990-01-01',
                'address' => 'Dirección de prueba',
                'place_sector_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('partners')->insert([
                'person_id' => $personId,
                'association_id' => 1,
                'state_id' => 1,
                'date_begin' => $dateBegin,
                'date_end' => $dateEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $partners = app(PartnerRepository::class)
            ->findActiveByAssociation(1, '2026-09-01', '2026-09-30');

        $this->assertCount(2, $partners);
        $this->assertSame(
            ['2026-08-01', '2026-09-20'],
            $partners->pluck('date_begin')->sort()->values()->all()
        );

        $partnerIds = DB::table('partners')->orderBy('id')->pluck('id');
        DB::table('partner_roster_periods')->insert([
            [
                'partner_id' => $partnerIds[0],
                'period' => '2026-08-01',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'partner_id' => $partnerIds[1],
                'period' => '2026-09-01',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $partners = app(PartnerRepository::class)
            ->findActiveByAssociation(1, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $partners);
        $this->assertSame('2026-09-20', $partners->first()->date_begin);
    }
}
