<?php

namespace Tests\Feature\Web;

use App\Models\Association;
use App\Models\Racion;
use App\Models\Resolution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class ClubReconocimientosWebTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();

        $now = now();

        DB::table('states')->insert([
            ['id' => 2, 'title' => 'Vigente', 'abbreviation' => 'VIG'],
            ['id' => 3, 'title' => 'Pendiente', 'abbreviation' => 'PEN'],
            ['id' => 4, 'title' => 'Vencido', 'abbreviation' => 'VEN'],
        ]);

        foreach (['club-madres', 'responsables-raciones'] as $order => $slug) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => $slug,
                'slug' => $slug,
                'description' => $slug,
                'icon' => 'fa-people-group',
                'route' => $slug,
                'order' => $order + 1,
                'is_active' => true,
            ]);

            DB::table('module_rol')->insert([
                'module_id' => $moduleId,
                'rol_id' => 1,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);
        }

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('resolutions')->insert([
            'id' => 1,
            'document' => 'RES-001',
            'date_document' => '2024-01-15 10:00:00',
            'date_start' => '2024-01-15',
            'date_end' => '2099-12-31',
            'state_id' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_padron_and_acta_reuniones_render_pdf(): void
    {
        Association::create([
            'code' => 'W04',
            'name' => 'Comité Padrón',
            'company_name' => 'CDM',
            'address' => 'Av. 4',
            'place_sector_id' => 1,
            'type_premises_id' => 1,
            'resolution_id' => 1,
            'state_id' => 2,
        ]);

        $user = $this->adminUser();

        $this->actingAs($user)
            ->get(route('club-reconocimientos.club.padron', ['month' => 3, 'year' => 2025]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('club-reconocimientos.acta-reuniones'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
