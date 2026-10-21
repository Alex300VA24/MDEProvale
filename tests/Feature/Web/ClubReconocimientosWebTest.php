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

    public function test_store_club_registers_committee_and_redirects(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('club-reconocimientos.store'), [
                'resolution_id' => 1,
                'name' => 'Comité Web',
                'code' => 'W01',
                'company_name' => 'Comité Web SAC',
                'address' => 'Av. Web 1',
                'place_sector_id' => 1,
                'type_premises_id' => 1,
            ])
            ->assertRedirect(route('club-reconocimientos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('associations', ['code' => 'W01', 'state_id' => 2]);
    }

    public function test_destroy_club_without_references_deletes(): void
    {
        $association = Association::create([
            'code' => 'W02',
            'name' => 'Comité Borrable',
            'company_name' => 'SAC',
            'address' => 'Av. 2',
            'place_sector_id' => 1,
            'type_premises_id' => 1,
            'resolution_id' => 1,
            'state_id' => 2,
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('club-reconocimientos.destroy', $association))
            ->assertRedirect(route('club-reconocimientos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('associations', ['id' => $association->id]);
    }

    public function test_store_and_destroy_resolution(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('club-reconocimientos.reconocimientos.store'), [
                'document' => 'RES-777',
                'date_document' => '2025-01-10',
                'date_start' => '2025-01-10',
                'date_end' => '2099-01-10',
            ])
            ->assertRedirect(route('club-reconocimientos.reconocimientos.index'));

        $resolution = Resolution::where('document', 'RES-777')->firstOrFail();

        $this->actingAs($this->adminUser())
            ->delete(route('club-reconocimientos.reconocimientos.destroy', $resolution))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('resolutions', ['id' => $resolution->id]);
    }

    public function test_destroy_resolution_in_use_is_rejected(): void
    {
        Association::create([
            'code' => 'W03',
            'name' => 'Comité con resolución',
            'company_name' => 'SAC',
            'address' => 'Av. 3',
            'place_sector_id' => 1,
            'type_premises_id' => 1,
            'resolution_id' => 1,
            'state_id' => 2,
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('club-reconocimientos.reconocimientos.destroy', 1))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('resolutions', ['id' => 1]);
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

    public function test_responsables_raciones_web_actions(): void
    {
        $now = now();
        DB::table('people')->insert([
            'id' => 1, 'names' => 'María', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno',
            'dni' => '12345671', 'gender' => 'F', 'birthdate' => '1990-01-01', 'address' => 'Calle 1',
            'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('responsables-raciones.responsibles.update', 'chief'), ['person_id' => 1])
            ->assertRedirect(route('responsables-raciones.index'));

        $this->assertDatabaseHas('responsibles', ['person_id' => 1, 'type' => 'chief', 'active' => true]);

        $racion = Racion::create([
            'year' => 2031,
            'month_start' => 1,
            'month_end' => 12,
            'racion_hojuelas_gramos' => 30,
            'racion_leche_militros' => 200,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('responsables-raciones.raciones.update', $racion), [
                'racion_hojuelas_gramos' => 40,
                'racion_leche_militros' => 250,
            ])
            ->assertRedirect(route('responsables-raciones.index'));

        $this->assertEquals(40, $racion->fresh()->racion_hojuelas_gramos);

        $this->actingAs($user)
            ->delete(route('responsables-raciones.raciones.destroy', $racion))
            ->assertRedirect(route('responsables-raciones.index'));

        $this->assertDatabaseMissing('raciones', ['id' => $racion->id]);
    }
}
