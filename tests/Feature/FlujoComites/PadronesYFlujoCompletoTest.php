<?php

namespace Tests\Feature\FlujoComites;

use App\Services\BeneficiaryReportService;
use App\Services\ClubReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\FlujoComiteFixtures;

/**
 * Paso 5 (padrones) y recorridos completos:
 * Resolución -> Comité -> Socios/Beneficiarios -> Cargos -> Padrones.
 */
class PadronesYFlujoCompletoTest extends TestCase
{
    use RefreshDatabase;
    use FlujoComiteFixtures;

    private int $mes;
    private int $anio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlujo();
        $this->mes = (int) now()->month;
        $this->anio = (int) now()->year;
    }

    private function padronBeneficiarios(int $comite, ?int $mes = null, ?int $anio = null, bool $json = true)
    {
        $url = '/socios-beneficiarios/beneficiarios-padron?association_id='.$comite.'&month='.($mes ?? $this->mes).'&year='.($anio ?? $this->anio);

        return $json
            ? $this->actingAs($this->admin())->getJson($url)
            : $this->actingAs($this->admin())->get($url);
    }

    private function padronClub(?int $mes = null, ?int $anio = null)
    {
        return $this->actingAs($this->admin())->getJson('/club-reconocimientos/club-padron?month='.($mes ?? $this->mes).'&year='.($anio ?? $this->anio));
    }

    /** Códigos de comité presentes en el padrón general del club. */
    private function codigosEnPadronClub(?int $mes = null, ?int $anio = null): array
    {
        $data = app(ClubReportService::class)->padron($mes ?? $this->mes, $anio ?? $this->anio);

        return collect($data['zonas'])->flatMap(fn ($z) => collect($z['sectores'])->flatMap(fn ($s) => collect($s['clubes'])->pluck('codigo')))->all();
    }

    // ============ PADRÓN DE BENEFICIARIOS ============

    public function test_padron_beneficiarios_pdf_con_datos(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P001');
        $this->socio($comite);

        $this->padronBeneficiarios($comite, json: false)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_padron_beneficiarios_exige_comite(): void
    {
        $this->actingAs($this->admin())->getJson('/socios-beneficiarios/beneficiarios-padron')->assertStatus(422);
    }

    public function test_padron_beneficiarios_de_comite_inexistente_da_404(): void
    {
        $this->padronBeneficiarios(99999)->assertNotFound();
    }

    public function test_padron_beneficiarios_comite_sin_socios_da_422_con_mensaje(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P002');
        $this->padronBeneficiarios($comite)->assertStatus(422)->assertJsonPath('message', 'No hay socios vigentes para el comité y periodo seleccionado.');
    }

    public function test_padron_beneficiarios_socios_sin_historial_vigente_da_422(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P003');
        $this->socio($comite, null, [$this->benef($this->nino(), null)]);   // beneficiario sin historial de beneficio

        $this->padronBeneficiarios($comite)->assertStatus(422)->assertJsonPath('message', 'No se encontraron beneficiarios vigentes para el comité y periodo seleccionados.');
    }

    public function test_padron_de_periodo_anterior_al_inicio_de_la_socia_se_rechaza(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P004');
        $this->socio($comite);

        $this->padronBeneficiarios($comite, 1, $this->anio - 3)->assertStatus(422);
    }

    public function test_padron_excluye_socia_dada_de_baja_antes_del_periodo(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P005');
        $s = $this->socio($comite);
        DB::table('partners')->where('id', $s)->update(['date_end' => now()->subMonths(2)->toDateString(), 'state_id' => $this->ven]);

        $this->padronBeneficiarios($comite)->assertStatus(422);
    }

    public function test_padron_cuenta_por_edad_sexo_y_tipo(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P006');
        $this->socio($comite, null, [
            $this->benef($this->nino(0, 'M', ['birthdate' => now()->subMonths(8)->toDateString()]), 'NI0'),
            $this->benef($this->nino(3, 'F'), 'NI0'),
            $this->benef($this->nino(9, 'M'), 'NI7'),
        ]);

        $data = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio);

        $this->assertSame(3, $data['total_beneficiarios']);
        $this->assertSame(3, $data['resumen']['total']['total_general']);
        $this->assertSame(1, $data['resumen']['total']['0_anos']);
        $this->assertSame(1, $data['resumen']['total']['3_anos']);
        $this->assertSame(1, $data['resumen']['total']['ninos_7_13']);
        $this->assertSame(2, $data['resumen']['masculino']['total_general']);
        $this->assertSame(1, $data['resumen']['femenino']['total_general']);
        $this->assertSame('P006', (string) $data['comite']);
    }

    public function test_padron_incluye_gestante_lactante_adulto_mayor_discapacitado_y_tbc(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P007');
        $hoy = now()->toDateString();

        // GESTANTE
        $g = $this->persona();
        $p = $this->socioPayload($g, $comite, [], ['socioeconomic_condition' => 'GESTANTE', 'fum' => now()->subMonths(3)->toDateString()]);
        unset($p['beneficiaries']);
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/partners', $p)->assertCreated();

        // LACTANTE
        $bebe = $this->nino(0, 'F', ['birthdate' => now()->subMonths(5)->toDateString()]);
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/partners', $this->socioPayload($this->persona(), $comite, [$this->benef($bebe, 'NI0')], ['socioeconomic_condition' => 'LACTANTE', 'benefit_child_person_id' => $bebe]))->assertCreated();

        // ADULTO MAYOR
        $p = $this->socioPayload($this->persona(['birthdate' => now()->subYears(72)->toDateString()]), $comite, [], ['socioeconomic_condition' => 'ADULTO_MAYOR']);
        unset($p['beneficiaries']);
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/partners', $p)->assertCreated();

        // TBC
        $p = $this->socioPayload($this->persona(), $comite, [], ['socioeconomic_condition' => 'OTROS', 'observations' => 'tuberculosis']);
        unset($p['beneficiaries']);
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/partners', $p)->assertCreated();

        $r = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio)['resumen']['total'];

        $this->assertSame(1, $r['madres_gestantes']);
        $this->assertSame(1, $r['madres_lactantes']);
        $this->assertSame(1, $r['ancianos']);
        $this->assertSame(1, $r['tuberculosos']);
    }

    public function test_padron_marca_baja_por_edad_de_hijo_de_14_anios_o_mas(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P008');
        $adolescente = $this->nino(15, 'M');
        $this->socio($comite, null, [$this->benef($adolescente, 'NI7'), $this->benef($this->nino(2), 'NI0')]);
        // NI7 vence a los 14 años y saldría del padrón; se simula un registro que no se cerró a tiempo.
        DB::table('beneficiary_histories')->whereIn('beneficiary_id', DB::table('beneficiaries')->where('person_id', $adolescente)->pluck('id'))->update(['date_end' => null]);

        $data = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio);

        $this->assertSame(1, $data['resumen']['total']['gap'], 'El hijo de 15 años debe contarse como baja.');
        $this->assertSame(1, $data['observaciones'][0]['cantidad']);
    }

    public function test_padron_marca_baja_de_gestante_con_mas_de_9_meses_de_fum(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P009');
        $p = $this->socioPayload($this->persona(), $comite, [], [
            'socioeconomic_condition' => 'GESTANTE', 'fum' => now()->subMonths(11)->toDateString(), 'date_begin' => now()->subMonths(11)->toDateString(),
        ]);
        unset($p['beneficiaries']);
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/partners', $p)->assertCreated();
        DB::table('beneficiary_histories')->whereNotNull('date_end')->where('type_benefit_id', $this->benefits['GES'])->update(['date_end' => null]);
        // otro beneficiario vigente para que el padrón exista
        $this->socio($comite);

        $data = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio);
        $this->assertGreaterThanOrEqual(1, $data['observaciones'][1]['cantidad']);
    }

    public function test_padron_toma_la_presidenta_vigente_o_sin_asignar(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'P010');
        $s = $this->socio($comite);

        $this->assertSame('SIN ASIGNAR', app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio)['presidenta']);

        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/asignar-cargo", ['partner_id' => $s, 'position' => 'PRESIDENTA'])->assertOk();
        $this->assertNotSame('SIN ASIGNAR', app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio)['presidenta']);
    }

    public function test_padron_beneficiarios_bloqueado_con_resolucion_pendiente_y_se_libera_al_regularizar(): void
    {
        $res = $this->resolucion('PEN', 'EN TRAMITE');
        $comite = $this->comite($res, 'P011');
        $this->socio($comite);

        $this->padronBeneficiarios($comite)->assertStatus(422);
        $this->actingAs($this->admin())->getJson('/socios-beneficiarios/beneficiarios-padron?association_id='.$comite.'&month='.$this->mes.'&year='.$this->anio)
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'Pendiente'));

        $this->portalPublica(['0700-2026']);
        $this->actingAs($this->admin())->postJson(self::COMITES."/reconocimientos/$res/regularizar", ['document' => '0700-2026'])->assertOk();

        $this->padronBeneficiarios($comite, json: false)->assertOk();
    }

    public function test_padron_no_se_bloquea_si_el_comite_esta_inactivo_aunque_tenga_resolucion_pendiente(): void
    {
        $res = $this->resolucion('PEN', 'TRAM2');
        $comite = $this->comite($res, 'P012');
        $this->socio($comite);
        DB::table('associations')->where('id', $comite)->update(['state_id' => $this->ina, 'state_source' => 'MANUAL']);

        $this->padronBeneficiarios($comite, json: false)->assertOk();
    }

    // ============ PADRÓN GENERAL DEL CLUB ============

    public function test_padron_club_incluye_el_comite_nuevo_y_responde_pdf(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'G001');
        $this->socio($comite);

        $this->assertContains('G001', $this->codigosEnPadronClub());
        $this->actingAs($this->admin())->get('/club-reconocimientos/club-padron?month='.$this->mes.'&year='.$this->anio)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_padron_club_cuenta_beneficiarios_del_comite(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'G002');
        $this->socio($comite, null, [$this->benef($this->nino(1), 'NI0'), $this->benef($this->nino(2), 'NI0')]);

        $data = app(ClubReportService::class)->padron($this->mes, $this->anio);
        $club = collect($data['zonas'])->flatMap(fn ($z) => collect($z['sectores'])->flatMap(fn ($s) => $s['clubes']))->firstWhere('codigo', 'G002');

        $this->assertSame(2, $club['beneficiarios']);
    }

    public function test_padron_club_excluye_comite_cuya_resolucion_aun_no_empezaba(): void
    {
        $comite = $this->comite($this->resolucion('VIG', null, $this->dias(40), $this->dias(400)), 'G003');
        $this->assertNotContains('G003', $this->codigosEnPadronClub());
    }

    public function test_padron_club_excluye_comite_con_resolucion_vencida_antes_del_mes(): void
    {
        $this->comite($this->resolucion('VEN', null, now()->subYear()->toDateString(), now()->subMonths(4)->toDateString()), 'G004');
        $this->assertNotContains('G004', $this->codigosEnPadronClub());
    }

    public function test_padron_club_muestra_presidenta_y_resoluciones(): void
    {
        $res = $this->resolucion('VIG', '0321-2026');
        $comite = $this->comite($res, 'G005');
        $s = $this->socio($comite);
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/asignar-cargo", ['partner_id' => $s, 'position' => 'PRESIDENTA'])->assertOk();

        $data = app(ClubReportService::class)->padron($this->mes, $this->anio);
        $club = collect($data['zonas'])->flatMap(fn ($z) => collect($z['sectores'])->flatMap(fn ($x) => $x['clubes']))->firstWhere('codigo', 'G005');

        $this->assertNotSame('', $club['presidenta']);
        $this->assertSame('0321-2026', $club['resolucion_1']);
    }

    public function test_padron_club_bloqueado_si_algun_comite_activo_tiene_resolucion_pendiente(): void
    {
        $this->comite($this->resolucion('PEN', 'TRAM3'), 'G006');
        $this->padronClub()->assertStatus(422);
    }

    public function test_padron_club_reintegra_tras_regularizar(): void
    {
        $res = $this->resolucion('PEN', 'TRAM4');
        $this->comite($res, 'G007');
        $this->padronClub()->assertStatus(422);

        $this->portalPublica(['0800-2026']);
        $this->actingAs($this->admin())->postJson(self::COMITES."/reconocimientos/$res/regularizar", ['document' => '0800-2026'])->assertOk();
        $this->padronClub()->assertOk();
    }

    public function test_actas_pdf_del_club(): void
    {
        $this->comite($this->resolucion('VIG'), 'A001');
        foreach (['acta-renuncia', 'acta-reuniones', 'acta-supervision'] as $acta) {
            $this->actingAs($this->admin())->get("/club-reconocimientos/$acta")->assertOk();
        }
    }

    public function test_padrones_permisos(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'PM1');
        $this->socio($comite);

        $this->actingAs($this->sinAcceso())->get('/club-reconocimientos/club-padron')->assertRedirect(route('dashboard'));
        $this->actingAs($this->sinAcceso())->get('/socios-beneficiarios/beneficiarios-padron?association_id='.$comite)->assertRedirect(route('dashboard'));
        $this->actingAs($this->lector())->get('/club-reconocimientos/club-padron?month='.$this->mes.'&year='.$this->anio)->assertOk();
    }

    public function test_padron_sin_sesion_redirige_al_login(): void
    {
        $this->app['auth']->forgetGuards();
        $this->get('/club-reconocimientos/club-padron')->assertRedirectContains('/login');
    }

    // ============ RECORRIDOS COMPLETOS ============

    /** Camino feliz: resolución VIGENTE (validada en portal). */
    public function test_flujo_completo_con_resolucion_vigente(): void
    {
        // 1. Resolución vigente validada contra el portal
        $this->portalPublica(['0450-2026']);
        $res = $this->actingAs($this->admin())->postJson(self::COMITES.'/reconocimientos', [
            'document' => '0450-2026', 'date_start' => $this->dias(-20), 'date_end' => $this->dias(340),
        ])->assertCreated()->assertJsonPath('data.state.abbreviation', 'VIG')->json('data.id');

        // 2. Comité
        $comite = $this->actingAs($this->admin())->postJson(self::COMITES.'/clubs', $this->clubPayload($res, 'F001'))
            ->assertCreated()->assertJsonPath('data.state.abbreviation', 'VIG')->json('data.id');

        // 3. Personas, socios y beneficiarios (sin condición, gestante y adulto mayor)
        $presidenta = $this->socio($comite);
        $tesorera = $this->socio($comite);
        $gest = $this->persona();
        $p = $this->socioPayload($gest, $comite, [], ['socioeconomic_condition' => 'GESTANTE', 'fum' => now()->subMonths(2)->toDateString()]);
        unset($p['beneficiaries']);
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/partners', $p)->assertCreated();
        $this->assertSame(3, DB::table('partners')->where('association_id', $comite)->count());

        // 4. Cargos
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/asignar-cargos", ['assignments' => [
            ['position' => 'PRESIDENTA', 'partner_id' => $presidenta],
            ['position' => 'TESORERA', 'partner_id' => $tesorera],
        ]])->assertOk()->assertJsonPath('data.president_partner_id', $presidenta);

        // 5. Padrones
        $this->padronBeneficiarios($comite, json: false)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertContains('F001', $this->codigosEnPadronClub());
        $this->actingAs($this->admin())->get('/club-reconocimientos/club-padron?month='.$this->mes.'&year='.$this->anio)->assertOk();

        $data = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio);
        $this->assertGreaterThanOrEqual(3, $data['resumen']['total']['total_general']);
        $this->assertNotSame('SIN ASIGNAR', $data['presidenta']);
    }

    /** Camino con resolución PENDIENTE: se opera, pero el padrón queda bloqueado hasta regularizar. */
    public function test_flujo_completo_con_resolucion_pendiente_y_regularizacion(): void
    {
        $res = $this->actingAs($this->admin())->postJson(self::COMITES.'/reconocimientos', [
            'document' => 'EN TRAMITE', 'date_start' => $this->dias(-5), 'date_end' => $this->dias(360),
            'pending' => true, 'observation' => 'A la espera de publicación',
        ])->assertCreated()->assertJsonPath('data.state.abbreviation', 'PEN')->json('data.id');

        $comite = $this->comite($res, 'F002');
        $s = $this->socio($comite);
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/asignar-cargo", ['partner_id' => $s, 'position' => 'PRESIDENTA'])->assertOk();

        // Padrones bloqueados
        $this->padronBeneficiarios($comite)->assertStatus(422);
        $this->padronClub()->assertStatus(422);

        // Regularización con el número real
        $this->portalPublica(['0460-2026']);
        $this->actingAs($this->admin())->postJson(self::COMITES."/reconocimientos/$res/regularizar", ['document' => '0460-2026'])
            ->assertOk()->assertJsonPath('data.state.abbreviation', 'VIG');

        // Padrones habilitados con el comité nuevo
        $this->padronBeneficiarios($comite, json: false)->assertOk();
        $this->assertContains('F002', $this->codigosEnPadronClub());
        $this->padronClub()->assertOk();
    }

    /** Renovación: comité cambia a una nueva resolución, queda historial y sigue en el padrón. */
    public function test_flujo_renovacion_de_resolucion_del_comite(): void
    {
        $vieja = $this->resolucion('VIG', '0100-2025', now()->subYear()->toDateString(), $this->dias(5));
        $comite = $this->comite($vieja, 'F003');
        $this->socio($comite);

        $nueva = $this->resolucion('VIG', '0101-2026', $this->dias(-2), $this->dias(365));
        DB::table('resolution_associations')->insert(['resolution_id' => $vieja, 'association_id' => $comite, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->admin())->putJson(self::COMITES."/clubs/$comite", ['resolution_id' => $nueva])->assertOk();

        $this->actingAs($this->admin())->getJson(self::COMITES."/clubs/$comite")->assertOk()
            ->assertJsonPath('data.resolution_id', $nueva)->assertJsonCount(2, 'data.all_resolutions');
        $this->assertContains('F003', $this->codigosEnPadronClub());
        $this->actingAs($this->admin())->delete(self::COMITES.'/reconocimientos/'.$vieja, [], ['Accept' => 'application/json'])->assertStatus(422);
    }

    /** Resolución vence: comité se inactiva y deja de salir en el padrón futuro. */
    public function test_flujo_vencimiento_de_resolucion_inactiva_comite_y_lo_saca_del_padron(): void
    {
        $res = $this->resolucion('VIG', null, now()->subMonths(8)->toDateString(), $this->dias(10));
        $comite = $this->comite($res, 'F004');
        $this->socio($comite, null, null, ['date_begin' => now()->subMonths(8)->toDateString()]);

        $this->actingAs($this->admin())->putJson(self::COMITES."/reconocimientos/$res", ['date_end' => now()->subMonths(1)->endOfMonth()->toDateString()])->assertOk();
        $this->assertDatabaseHas('associations', ['id' => $comite, 'state_id' => $this->ina]);

        $this->assertNotContains('F004', $this->codigosEnPadronClub());
        $this->assertContains('F004', $this->codigosEnPadronClub(now()->subMonths(3)->month, now()->subMonths(3)->year));
    }

    /** Renuncia del comité (baja manual) mantiene su estado aun con resolución vigente. */
    public function test_flujo_renuncia_del_comite_con_acta(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'F005');
        $this->socio($comite);

        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/status-change", [
            'state_id' => $this->ina, 'reason' => 'RENUNCIA', 'effective_date' => $this->dias(0), 'acta' => $this->pdf('acta.pdf'),
        ])->assertOk();

        $this->actingAs($this->admin())->getJson(self::COMITES.'/clubs')->assertOk();
        $this->assertDatabaseHas('associations', ['id' => $comite, 'state_id' => $this->ina, 'state_source' => 'MANUAL']);
    }

    /** Cambios de directiva tras el alta de nuevas socias. */
    public function test_flujo_cambio_de_directiva_y_nueva_socia_en_el_padron(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'F006');
        $a = $this->socio($comite);
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/asignar-cargo", ['partner_id' => $a, 'position' => 'PRESIDENTA'])->assertOk();
        $antes = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio)['resumen']['total']['total_general'];

        $this->socio($comite, null, [$this->benef($this->nino(1), 'NI0'), $this->benef($this->nino(2), 'NI0')]);
        $despues = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio)['resumen']['total']['total_general'];

        $this->assertSame($antes + 2, $despues);
    }

    /** Eliminar socio la saca del padrón. */
    public function test_flujo_eliminar_socio_la_quita_del_padron(): void
    {
        $comite = $this->comite($this->resolucion('VIG'), 'F007');
        $a = $this->socio($comite);
        $b = $this->socio($comite);
        $antes = app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio)['total_beneficiarios'];

        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/partners/$b")->assertNoContent();

        $this->assertSame($antes - 1, app(BeneficiaryReportService::class)->generatePadronReport($comite, $this->mes, $this->anio)['total_beneficiarios']);
    }
}
