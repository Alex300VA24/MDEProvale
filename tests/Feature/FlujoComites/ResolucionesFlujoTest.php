<?php

namespace Tests\Feature\FlujoComites;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\FlujoComiteFixtures;

/** Paso 1 del flujo: registrar / regularizar / editar / eliminar resoluciones. */
class ResolucionesFlujoTest extends TestCase
{
    use RefreshDatabase;
    use FlujoComiteFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlujo();
    }

    private function payload(string $doc = '0123-2026', array $over = []): array
    {
        return array_merge([
            'document' => $doc,
            'date_start' => $this->dias(-10),
            'date_end' => $this->dias(350),
        ], $over);
    }

    private function crear(array $data)
    {
        return $this->actingAs($this->admin())->postJson(self::COMITES.'/reconocimientos', $data);
    }

    // ============ VIGENTE (valida contra portal municipal) ============

    public function test_vigente_se_registra_si_el_portal_la_publica(): void
    {
        $this->portalPublica(['0123-2026']);

        $this->crear($this->payload())
            ->assertCreated()
            ->assertJsonPath('data.document', '0123-2026')
            ->assertJsonPath('data.state.abbreviation', 'VIG');

        $this->assertDatabaseHas('resolutions', ['document' => '0123-2026', 'state_id' => $this->vig, 'observation' => null]);
    }

    public function test_vigente_con_fecha_fin_pasada_nace_vencida(): void
    {
        $this->portalPublica(['0124-2026']);

        $this->crear($this->payload('0124-2026', ['date_start' => $this->dias(-400), 'date_end' => $this->dias(-5)]))
            ->assertCreated()
            ->assertJsonPath('data.state.abbreviation', 'VEN');
    }

    public function test_vigente_termina_hoy_sigue_vigente(): void
    {
        $this->portalPublica(['0125-2026']);

        $this->crear($this->payload('0125-2026', ['date_end' => $this->dias(0)]))
            ->assertCreated()
            ->assertJsonPath('data.state.abbreviation', 'VIG');
    }

    public function test_vigente_rechaza_numero_que_el_portal_no_publica(): void
    {
        $this->portalPublica(['9999-2026']);

        $this->crear($this->payload('0123-2026'))
            ->assertStatus(422)
            ->assertJsonPath('reason', 'not_found');

        $this->assertDatabaseMissing('resolutions', ['document' => '0123-2026']);
    }

    /** @dataProvider formatosInvalidos */
    public function test_vigente_rechaza_formato_invalido(string $doc): void
    {
        $this->portalPublica(['0123-2026']);

        $this->crear($this->payload($doc))
            ->assertStatus(422)
            ->assertJsonPath('reason', 'invalid_format');

        $this->assertDatabaseMissing('resolutions', ['document' => $doc]);
    }

    public static function formatosInvalidos(): array
    {
        return [['RES-001'], ['123'], ['123-26'], ['123-2026-A'], ['ABC-2026'], ['123/2026']];
    }

    public function test_vigente_con_portal_caido_no_guarda_ni_dice_que_no_existe(): void
    {
        $this->portalCaido();

        $response = $this->crear($this->payload());

        $this->assertNotSame(201, $response->status());
        $this->assertNotSame('not_found', $response->json('reason'), 'Un corte de internet no debe reportarse como "no existe".');
        $this->assertDatabaseMissing('resolutions', ['document' => '0123-2026']);
    }

    public function test_vigente_con_portal_en_error_500_no_guarda(): void
    {
        $this->portalConError(500);

        $this->assertNotSame(201, $this->crear($this->payload())->status());
        $this->assertDatabaseMissing('resolutions', ['document' => '0123-2026']);
    }

    /** @dataProvider camposObligatorios */
    public function test_campos_obligatorios(string $campo): void
    {
        $data = $this->payload();
        unset($data[$campo]);

        $this->crear($data)->assertStatus(422)->assertJsonValidationErrors($campo);
    }

    public static function camposObligatorios(): array
    {
        return [['document'], ['date_start'], ['date_end']];
    }

    public function test_fecha_fin_menor_que_inicio_se_rechaza(): void
    {
        $this->portalPublica(['0123-2026']);

        $this->crear($this->payload('0123-2026', ['date_start' => $this->dias(10), 'date_end' => $this->dias(5)]))
            ->assertStatus(422)->assertJsonValidationErrors('date_end');
    }

    public function test_fechas_con_formato_invalido_se_rechazan(): void
    {
        $this->crear($this->payload('0123-2026', ['date_start' => 'ayer']))->assertStatus(422)->assertJsonValidationErrors('date_start');
    }

    public function test_documento_mayor_a_100_caracteres_se_rechaza(): void
    {
        $this->crear($this->payload(str_repeat('1', 101).'-2026'))->assertStatus(422)->assertJsonValidationErrors('document');
    }

    public function test_fecha_inicio_igual_a_fin_es_valida(): void
    {
        $this->portalPublica(['0126-2026']);

        $this->crear($this->payload('0126-2026', ['date_start' => $this->dias(5), 'date_end' => $this->dias(5)]))->assertCreated();
    }

    /** @group hallazgos */
    public function test_documento_duplicado_se_rechaza(): void
    {
        $this->portalPublica(['0123-2026']);
        $this->crear($this->payload())->assertCreated();

        // Regla de negocio esperada: un mismo número de resolución no debería registrarse dos veces.
        $this->crear($this->payload())->assertStatus(422);
        $this->assertSame(1, DB::table('resolutions')->where('document', '0123-2026')->count());
    }

    public function test_resolucion_historica_con_documento_repetido_se_admite(): void
    {
        DB::table('resolutions')->insert(['document' => '0123-2026', 'date_start' => $this->dias(-900), 'date_end' => $this->dias(-400), 'state_id' => $this->ven, 'created_at' => now(), 'updated_at' => now()]);
        $this->portalPublica(['0123-2026']);

        $this->crear($this->payload('0123-2026', ['date_start' => $this->dias(-800), 'date_end' => $this->dias(-100)]))->assertCreated();
        $this->assertSame(2, DB::table('resolutions')->where('document', '0123-2026')->count());
    }

    // ============ PENDIENTE (sin validación de portal) ============

    public function test_pendiente_exige_observacion(): void
    {
        $this->crear($this->payload('S/N', ['pending' => true]))
            ->assertStatus(422)->assertJsonValidationErrors('observation');
    }

    public function test_pendiente_con_observacion_solo_espacios_se_rechaza(): void
    {
        $this->crear($this->payload('S/N', ['pending' => true, 'observation' => '    ']))
            ->assertStatus(422);
        $this->assertDatabaseMissing('resolutions', ['document' => 'S/N']);
    }

    public function test_pendiente_no_consulta_el_portal_y_acepta_cualquier_formato(): void
    {
        $this->portalCaido();

        $this->crear($this->payload('EN TRAMITE', ['pending' => true, 'observation' => 'Falta publicar']))
            ->assertCreated()
            ->assertJsonPath('data.state.abbreviation', 'PEN')
            ->assertJsonPath('data.observation', 'Falta publicar');

        $this->assertDatabaseHas('notifications', ['type' => 'regularizacion', 'status' => 'por_solucionar']);
    }

    public function test_pendiente_aunque_la_fecha_fin_haya_pasado_sigue_pendiente(): void
    {
        $this->crear($this->payload('777-2026', ['pending' => true, 'observation' => 'x', 'date_start' => $this->dias(-400), 'date_end' => $this->dias(-10)]))
            ->assertCreated()->assertJsonPath('data.state.abbreviation', 'PEN');

        // El listado sincroniza estados: no debe pasarla a VEN.
        $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos')->assertOk();
        $this->assertDatabaseHas('resolutions', ['document' => '777-2026', 'state_id' => $this->pen]);
    }

    public function test_pending_false_se_trata_como_vigente_y_valida_portal(): void
    {
        $this->portalPublica([]);

        $this->crear($this->payload('0123-2026', ['pending' => false, 'observation' => 'ignorada']))
            ->assertStatus(422)->assertJsonPath('reason', 'not_found');
    }

    public function test_vigente_descarta_la_observacion_enviada(): void
    {
        $this->portalPublica(['0123-2026']);

        $this->crear($this->payload('0123-2026', ['observation' => 'no debería guardarse']))->assertCreated();
        $this->assertDatabaseHas('resolutions', ['document' => '0123-2026', 'observation' => null]);
    }

    // ============ REGULARIZAR (Pendiente -> Vigente/Vencido) ============

    private function pendiente(string $doc = 'TRAMITE'): int
    {
        return $this->crear($this->payload($doc, ['pending' => true, 'observation' => 'Falta publicar']))->assertCreated()->json('data.id');
    }

    private function regularizar(int $id, array $data)
    {
        return $this->actingAs($this->admin())->postJson(self::COMITES."/reconocimientos/$id/regularizar", $data);
    }

    public function test_regularizar_pasa_a_vigente_y_resuelve_la_notificacion(): void
    {
        $id = $this->pendiente();
        $this->portalPublica(['0500-2026']);

        $this->regularizar($id, ['document' => '0500-2026'])
            ->assertOk()->assertJsonPath('data.state.abbreviation', 'VIG');

        $this->assertDatabaseHas('resolutions', ['id' => $id, 'document' => '0500-2026', 'observation' => null, 'state_id' => $this->vig]);
        $this->assertDatabaseMissing('notifications', ['dedupe_key' => 'regularizacion_'.$id, 'status' => 'por_solucionar']);
    }

    public function test_regularizar_con_fechas_pasadas_queda_vencida(): void
    {
        $id = $this->pendiente();
        $this->portalPublica(['0501-2026']);

        $this->regularizar($id, ['document' => '0501-2026', 'date_start' => $this->dias(-300), 'date_end' => $this->dias(-1)])
            ->assertOk()->assertJsonPath('data.state.abbreviation', 'VEN');
    }

    public function test_regularizar_puede_corregir_fechas(): void
    {
        $id = $this->pendiente();
        $this->portalPublica(['0502-2026']);

        $this->regularizar($id, ['document' => '0502-2026', 'date_start' => '2026-01-01', 'date_end' => '2027-12-31'])->assertOk();
        $this->assertDatabaseHas('resolutions', ['id' => $id, 'date_start' => '2026-01-01', 'date_end' => '2027-12-31']);
    }

    public function test_regularizar_rechaza_si_no_esta_en_el_portal_y_sigue_pendiente(): void
    {
        $id = $this->pendiente();
        $this->portalPublica(['1-2026']);

        $this->regularizar($id, ['document' => '0503-2026'])->assertStatus(422);
        $this->assertDatabaseHas('resolutions', ['id' => $id, 'state_id' => $this->pen, 'document' => 'TRAMITE']);
        $this->assertDatabaseHas('notifications', ['dedupe_key' => 'regularizacion_'.$id, 'status' => 'por_solucionar']);
    }

    public function test_regularizar_con_portal_caido_no_cambia_el_estado(): void
    {
        $id = $this->pendiente();
        $this->portalCaido();

        $this->assertNotSame(200, $this->regularizar($id, ['document' => '0504-2026'])->status());
        $this->assertDatabaseHas('resolutions', ['id' => $id, 'state_id' => $this->pen]);
    }

    public function test_regularizar_exige_formato_valido(): void
    {
        $id = $this->pendiente();
        $this->regularizar($id, ['document' => 'ABC'])->assertStatus(422)->assertJsonValidationErrors('document');
        $this->regularizar($id, [])->assertStatus(422)->assertJsonValidationErrors('document');
    }

    public function test_regularizar_resolucion_que_no_esta_pendiente_se_rechaza(): void
    {
        $id = $this->resolucion('VIG');
        $this->portalPublica(['0505-2026']);

        $this->regularizar($id, ['document' => '0505-2026'])
            ->assertStatus(422)->assertJsonPath('message', 'Solo se pueden regularizar resoluciones en estado Pendiente.');
    }

    public function test_regularizar_fecha_fin_menor_a_inicio_se_rechaza(): void
    {
        $id = $this->pendiente();
        $this->portalPublica(['0506-2026']);

        $this->regularizar($id, ['document' => '0506-2026', 'date_start' => $this->dias(10), 'date_end' => $this->dias(1)])
            ->assertStatus(422)->assertJsonValidationErrors('date_end');
    }

    public function test_regularizar_sincroniza_el_estado_del_comite_vinculado(): void
    {
        $id = $this->pendiente();
        DB::table('resolutions')->where('id', $id)->update(['date_end' => $this->dias(-3)]);
        $comite = $this->comite($id, 'R01');
        $this->portalPublica(['0507-2026']);

        // Fechas vencidas al regularizar => el comité debe quedar INACTIVO.
        $this->regularizar($id, ['document' => '0507-2026', 'date_start' => $this->dias(-100), 'date_end' => $this->dias(-3)])->assertOk();

        $this->assertDatabaseHas('associations', ['id' => $comite, 'state_id' => $this->ina]);
    }

    // ============ EDITAR ============

    private function editar(int $id, array $data)
    {
        return $this->actingAs($this->admin())->putJson(self::COMITES."/reconocimientos/$id", $data);
    }

    public function test_editar_fecha_fin_al_pasado_vence_la_resolucion_e_inactiva_el_comite(): void
    {
        $res = $this->resolucion('VIG');
        $comite = $this->comite($res, 'E01');
        $this->assertDatabaseHas('associations', ['id' => $comite, 'state_id' => $this->vig]);

        $this->editar($res, ['date_end' => $this->dias(-1)])->assertOk()->assertJsonPath('data.state.abbreviation', 'VEN');
        $this->assertDatabaseHas('associations', ['id' => $comite, 'state_id' => $this->ina]);
    }

    public function test_renovar_resolucion_vencida_reactiva_resolucion_y_comite(): void
    {
        $res = $this->resolucion('VEN');
        $comite = $this->comite($res, 'E02');
        $this->assertDatabaseHas('associations', ['id' => $comite, 'state_id' => $this->ina]);

        $this->editar($res, ['date_end' => $this->dias(200)])->assertOk()->assertJsonPath('data.state.abbreviation', 'VIG');
        $this->assertDatabaseHas('associations', ['id' => $comite, 'state_id' => $this->vig]);
    }

    public function test_editar_fecha_fin_menor_a_inicio_se_rechaza(): void
    {
        $res = $this->resolucion('VIG');
        $this->editar($res, ['date_start' => $this->dias(10), 'date_end' => $this->dias(5)])->assertStatus(422);
    }

    public function test_editar_pendiente_mantiene_estado_pendiente(): void
    {
        $id = $this->pendiente();
        $this->editar($id, ['date_end' => $this->dias(-30), 'date_start' => $this->dias(-60)])->assertOk();
        $this->assertDatabaseHas('resolutions', ['id' => $id, 'state_id' => $this->pen]);
    }

    public function test_editar_resolucion_inexistente_da_404(): void
    {
        $this->editar(99999, ['document' => 'X'])->assertNotFound();
    }

    public function test_editar_documento_a_vacio_se_rechaza(): void
    {
        $res = $this->resolucion('VIG');
        $this->editar($res, ['document' => ''])->assertStatus(422);
    }

    // ============ ELIMINAR ============

    private function borrar(int $id)
    {
        return $this->actingAs($this->admin())->deleteJson(self::COMITES."/reconocimientos/$id");
    }

    public function test_eliminar_resolucion_libre(): void
    {
        $res = $this->resolucion('VIG');
        $this->borrar($res)->assertNoContent();
        $this->assertDatabaseMissing('resolutions', ['id' => $res]);
    }

    public function test_eliminar_resolucion_borra_tambien_el_comite_vacio_que_depende_de_ella(): void
    {
        $res = $this->resolucion('VIG');
        $comite = $this->comite($res, 'D01');

        $this->borrar($res)->assertNoContent();
        $this->assertDatabaseMissing('associations', ['id' => $comite]);
    }

    public function test_eliminar_resolucion_con_comite_con_socios_se_bloquea_y_no_borra_nada(): void
    {
        $res = $this->resolucion('VIG');
        $comite = $this->comite($res, 'D02');
        $this->socio($comite);

        $this->borrar($res)->assertStatus(422);
        $this->assertDatabaseHas('resolutions', ['id' => $res]);
        $this->assertDatabaseHas('associations', ['id' => $comite]);
    }

    public function test_eliminar_resolucion_con_historial_de_otro_comite_se_bloquea(): void
    {
        $vieja = $this->resolucion('VEN');
        $nueva = $this->resolucion('VIG');
        $comite = $this->comite($vieja, 'D03');
        // Renovación: el comité pasa a la nueva resolución y la vieja queda en el historial.
        DB::table('resolution_associations')->insert(['resolution_id' => $vieja, 'association_id' => $comite, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('associations')->where('id', $comite)->update(['resolution_id' => $nueva]);

        $this->borrar($vieja)->assertStatus(422);
        $this->assertDatabaseHas('resolutions', ['id' => $vieja]);
    }

    public function test_eliminar_pendiente_borra_su_notificacion(): void
    {
        $id = $this->pendiente();
        $this->borrar($id)->assertNoContent();
        $this->assertDatabaseMissing('notifications', ['dedupe_key' => 'regularizacion_'.$id]);
    }

    // ============ LISTADO / OPCIONES / FILTROS ============

    public function test_listado_sincroniza_vencimiento_automatico(): void
    {
        $id = $this->resolucion('VIG', null, $this->dias(-100), $this->dias(-1));

        $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos')->assertOk();
        $this->assertDatabaseHas('resolutions', ['id' => $id, 'state_id' => $this->ven]);
    }

    public function test_listado_filtra_por_estado_busqueda_y_anio(): void
    {
        $this->resolucion('VIG', '0001-2026', '2026-01-10', $this->dias(300));
        $this->resolucion('PEN', 'PEND-77', '2025-03-10', $this->dias(300));

        $get = fn (string $q) => $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos?'.$q)->assertOk()->json('data');

        $this->assertCount(1, $get('state_id='.$this->pen));
        $this->assertSame('PEND-77', $get('search=PEND')[0]['document']);
        $this->assertCount(1, $get('anio=2025'));
        $this->assertCount(1, $get('anio=2026&mes=1'));
        $this->assertCount(0, $get('search=zzzz'));
    }

    public function test_options_devuelve_estados_y_anios(): void
    {
        $this->resolucion('VIG');
        $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos/options')
            ->assertOk()->assertJsonStructure(['states', 'years']);
    }

    // ============ PORTAL EXTERNO ============

    public function test_verificar_externa_encontrada(): void
    {
        $this->portalPublica(['0900-2026']);
        $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos/verificar-externa?document=0900-2026')
            ->assertOk()->assertJsonPath('success', true);
    }

    public function test_verificar_externa_no_encontrada_formato_y_requerido(): void
    {
        $this->portalPublica(['0900-2026']);
        $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos/verificar-externa?document=0901-2026')->assertNotFound()->assertJsonPath('reason', 'not_found');
        $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos/verificar-externa?document=XX')->assertStatus(422)->assertJsonPath('reason', 'invalid_format');
        $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos/verificar-externa')->assertStatus(422);
    }

    public function test_verificar_externa_con_portal_caido(): void
    {
        $this->portalCaido();
        $r = $this->actingAs($this->admin())->getJson(self::COMITES.'/reconocimientos/verificar-externa?document=0900-2026');
        $this->assertGreaterThanOrEqual(500, $r->status());
        $this->assertFalse((bool) $r->json('success'));
    }

    public function test_buscar_preview_y_descargar_externa(): void
    {
        $res = $this->resolucion('VIG', '0900-2026');
        $this->portalPublica(['0900-2026']);

        $this->actingAs($this->admin())->getJson(self::COMITES."/reconocimientos/$res/buscar-externa")->assertOk()->assertJsonPath('success', true);
        $this->actingAs($this->admin())->get(self::COMITES."/reconocimientos/$res/preview-externa")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->admin())->get(self::COMITES."/reconocimientos/$res/descargar-externa")->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="Resolucion-0900-2026-MDE.pdf"');
    }

    public function test_externa_de_resolucion_no_publicada_da_404(): void
    {
        $res = $this->resolucion('VIG', '0800-2026');
        $this->portalPublica(['0900-2026']);

        $this->actingAs($this->admin())->getJson(self::COMITES."/reconocimientos/$res/buscar-externa")->assertNotFound();
        $this->actingAs($this->admin())->get(self::COMITES."/reconocimientos/$res/preview-externa")->assertNotFound();
    }

    public function test_externa_con_archivo_que_no_es_pdf_da_502(): void
    {
        $res = $this->resolucion('VIG', '0900-2026');
        $row = '<table><tr class="od"><td>1</td><td>x</td><td>RESOLUCION 0900-2026</td><td>01/01/2026</td><td><a onclick="window.open(\'../f/a.pdf\')">p</a></td></tr></table>';
        \Illuminate\Support\Facades\Http::fake(fn ($r) => str_ends_with($r->url(), '.pdf') ? \Illuminate\Support\Facades\Http::response('<html>no pdf</html>', 200) : \Illuminate\Support\Facades\Http::response($row, 200));
        \Illuminate\Support\Facades\Cache::flush();

        $this->actingAs($this->admin())->get(self::COMITES."/reconocimientos/$res/preview-externa")->assertStatus(502);
    }

    // ============ PERMISOS ============

    public function test_sin_sesion_da_401(): void
    {
        $this->postJson(self::COMITES.'/reconocimientos', $this->payload())->assertUnauthorized();
        $this->getJson(self::COMITES.'/reconocimientos')->assertUnauthorized();
    }

    public function test_usuario_sin_modulo_da_403(): void
    {
        $this->actingAs($this->sinAcceso())->getJson(self::COMITES.'/reconocimientos')->assertForbidden();
    }

    public function test_lector_puede_ver_pero_no_crear_editar_ni_eliminar(): void
    {
        $res = $this->resolucion('VIG');
        $lector = $this->lector();

        $this->actingAs($lector)->getJson(self::COMITES.'/reconocimientos')->assertOk();
        $this->actingAs($lector)->postJson(self::COMITES.'/reconocimientos', $this->payload('X-1', ['pending' => true, 'observation' => 'x']))->assertForbidden();
        $this->actingAs($lector)->putJson(self::COMITES."/reconocimientos/$res", ['document' => 'Z'])->assertForbidden();
        $this->actingAs($lector)->deleteJson(self::COMITES."/reconocimientos/$res")->assertForbidden();
        $this->actingAs($lector)->postJson(self::COMITES."/reconocimientos/$res/regularizar", ['document' => '1-2026'])->assertForbidden();
        $this->assertDatabaseHas('resolutions', ['id' => $res]);
    }
}
