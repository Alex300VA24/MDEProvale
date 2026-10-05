<?php

namespace Tests\Unit;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class PartnerPositionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    public function test_partner_can_load_its_position(): void
    {
        $this->seedBaseData();
        DB::table('places')->insert(['id' => 1, 'code' => 'Z01', 'title' => 'Zona']);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector']);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local']);
        DB::table('resolutions')->insert(['id' => 1, 'document' => 'RES-001', 'state_id' => 1]);
        DB::table('associations')->insert([
            'id' => 1,
            'code' => 'C01',
            'name' => 'Comité',
            'company_name' => 'Comité',
            'address' => 'Calle 1',
            'resolution_id' => 1,
            'state_id' => 1,
            'place_sector_id' => 1,
            'type_premises_id' => 1,
        ]);
        DB::table('people')->insert([
            'id' => 1,
            'names' => 'María',
            'father_lastname' => 'Prueba',
            'mother_lastname' => 'Test',
            'dni' => '12345678',
            'gender' => 'F',
            'birthdate' => '1990-01-01',
            'address' => 'Calle 1',
            'place_sector_id' => 1,
        ]);

        $positionId = DB::table('positions')->insertGetId([
            'title' => 'PRESIDENTA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partnerId = DB::table('partners')->insertGetId([
            'person_id' => 1,
            'association_id' => 1,
            'state_id' => 1,
            'position_id' => $positionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partner = Partner::find($partnerId);

        $this->assertNotNull($partner);
        $this->assertNotNull($partner->position);
        $this->assertSame('PRESIDENTA', $partner->position->title);
    }
}
