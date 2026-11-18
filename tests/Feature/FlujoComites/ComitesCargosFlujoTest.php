<?php

namespace Tests\Feature\FlujoComites;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\FlujoComiteFixtures;

/** Pasos 2 y 4 del flujo: registrar comité y asignar cargos de la directiva. */
class ComitesCargosFlujoTest extends TestCase
{
    use RefreshDatabase;
    use FlujoComiteFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlujo();
    }

    private function crearClub(array $payload)
    {
        return $this->actingAs($this->admin())->postJson(self::COMITES.'/clubs', $payload);
    }

    // ============ REGISTRO DE COMITÉ ============

    public function test_comite_con_resolucion_vigente_nace_vigente(): void
    {
        $res = $this->resolucion('VIG');
        $this->crearClub($this->clubPayload($res))
            ->assertCreated()
            ->assertJsonPath('data.state.abbreviation', 'VIG')
            ->assertJsonPath('data.resolution_id', $res)
            ->assertJsonPath('data.state_source', 'AUTOMATIC');

        $this->assertDatabaseHas('associations', ['code' => 'C001', 'state_source' => 'AUTOMATIC']);
    }

    public function test_comite_con_resolucion_vencida_nace_inactivo(): void
    {
        $res = $this->resolucion('VEN');
        $this->crearClub($this->clubPayload($res))->assertCreated()->assertJsonPath('data.state.abbreviation', 'INA');
    }

    public function test_comite_con_resolucion_pendiente_nace_vigente_y_queda_en_historial_de_resolucion(): void
    {
        $res = $this->resolucion('PEN');
        $this->crearClub($this->clubPayload($res))
            ->assertCreated()
            ->assertJsonPath('data.state.abbreviation', 'VIG')
            ->assertJsonPath('data.resolution.state.abbreviation', 'PEN');
    }

    public function test_options_lista_vigentes_y_pendientes_pero_no_vencidas(): void
    {
        $vig = $this->resolucion('VIG', '0001-2026');
        $pen = $this->resolucion('PEN', 'PEND');
        $ven = $this->resolucion('VEN', '0002-2025');

        $ids = collect($this->actingAs($this->admin())->getJson(self::COMITES.'/clubs/options')->assertOk()->json('resolutions'))->pluck('id');

        $this->assertTrue($ids->contains($vig));
        $this->assertTrue($ids->contains($pen), 'Una resolución pendiente debe poder usarse para crear comité.');
        $this->assertFalse($ids->contains($ven));
    }

    public function test_options_con_include_resolution_id_agrega_la_vencida_pedida(): void
    {
        $ven = $this->resolucion('VEN');
        $ids = collect($this->actingAs($this->admin())->getJson(self::COMITES.'/clubs/options?include_resolution_id='.$ven)->json('resolutions'))->pluck('id');
        $this->assertTrue($ids->contains($ven));
    }

    public function test_options_next_code_sigue_al_mayor_codigo_numerico(): void
    {
        $res = $this->resolucion('VIG');
        $this->comite($res, '12');
        $this->comite($res, 'ABC');

        $this->actingAs($this->admin())->getJson(self::COMITES.'/clubs/options')->assertJsonPath('next_code', '13');
    }

    /** @dataProvider camposRequeridos */
    public function test_comite_campos_obligatorios(string $campo): void
    {
        $p = $this->clubPayload($this->resolucion('VIG'));
        unset($p[$campo]);
        $this->crearClub($p)->assertStatus(422)->assertJsonValidationErrors($campo);
    }

    public static function camposRequeridos(): array
    {
        return [['code'], ['name'], ['company_name'], ['address'], ['resolution_id'], ['place_sector_id'], ['type_premises_id']];
    }

    public function test_comite_referencias_inexistentes_se_rechazan(): void
    {
        $this->crearClub($this->clubPayload(99999))->assertStatus(422)->assertJsonValidationErrors('resolution_id');
        $this->crearClub($this->clubPayload($this->resolucion('VIG'), 'X1', ['place_sector_id' => 999]))->assertStatus(422)->assertJsonValidationErrors('place_sector_id');
        $this->crearClub($this->clubPayload($this->resolucion('VIG'), 'X2', ['type_premises_id' => 999]))->assertStatus(422)->assertJsonValidationErrors('type_premises_id');
    }

    public function test_comite_limites_de_longitud(): void
    {
        $res = $this->resolucion('VIG');
        $this->crearClub($this->clubPayload($res, str_repeat('A', 21)))->assertStatus(422)->assertJsonValidationErrors('code');
        $this->crearClub($this->clubPayload($res, 'L1', ['name' => str_repeat('n', 101)]))->assertStatus(422)->assertJsonValidationErrors('name');
        $this->crearClub($this->clubPayload($res, 'L2', ['phone' => str_repeat('9', 21)]))->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_comite_codigo_duplicado_exacto_y_numericamente_equivalente(): void
    {
        $res = $this->resolucion('VIG');
        $this->comite($res, '7');

        $this->crearClub($this->clubPayload($res, '7'))->assertStatus(422)->assertJsonValidationErrors('code');
        $this->crearClub($this->clubPayload($res, '007'))->assertStatus(422)->assertJsonValidationErrors('code');
        $this->crearClub($this->clubPayload($res, ' 7 '))->assertStatus(422);
    }

    public function test_telefono_y_observacion_son_opcionales(): void
    {
        $p = $this->clubPayload($this->resolucion('VIG'));
        unset($p['phone']);
        $this->crearClub($p)->assertCreated();
    }

    public function test_dos_comites_pueden_compartir_la_misma_resolucion(): void
    {
        $res = $this->resolucion('VIG');
        $this->comite($res, 'A1');
        $this->comite($res, 'A2');
        $this->assertSame(2, DB::table('associations')->where('resolution_id', $res)->count());
    }

    // ============ EDICIÓN / ESTADO ============

    private function putClub(int $id, array $data)
    {
        return $this->actingAs($this->admin())->putJson(self::COMITES."/clubs/$id", $data);
    }

    public function test_editar_comite_conserva_su_propio_codigo_y_rechaza_el_de_otro(): void
    {
        $res = $this->resolucion('VIG');
        $a = $this->comite($res, 'U1');
        $this->comite($res, 'U2');

        $this->putClub($a, ['code' => 'U1', 'name' => 'Nuevo nombre'])->assertOk()->assertJsonPath('data.name', 'Nuevo nombre');
        $this->putClub($a, ['code' => 'U2'])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_cambiar_resolucion_a_vencida_inactiva_el_comite_y_a_vigente_lo_reactiva(): void
    {
        $vig = $this->resolucion('VIG');
        $ven = $this->resolucion('VEN');
        $a = $this->comite($vig, 'S1');

        $this->putClub($a, ['resolution_id' => $ven])->assertOk();
        $this->assertDatabaseHas('associations', ['id' => $a, 'state_id' => $this->ina]);

        $this->putClub($a, ['resolution_id' => $vig])->assertOk();
        $this->assertDatabaseHas('associations', ['id' => $a, 'state_id' => $this->vig]);
    }

    public function test_editar_comite_inexistente_da_404(): void
    {
        $this->putClub(9999, ['name' => 'x'])->assertNotFound();
    }

    private function cambiarEstado(int $id, array $data)
    {
        return $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$id/status-change", $data);
    }

    public function test_renuncia_exige_acta_pdf_para_inactivar(): void
    {
        $a = $this->comite($this->resolucion('VIG'), 'M1');
        $data = ['state_id' => $this->ina, 'reason' => 'RENUNCIA', 'effective_date' => $this->dias(0)];

        $this->cambiarEstado($a, $data)->assertStatus(422)->assertJsonValidationErrors('acta');
        $this->assertDatabaseHas('associations', ['id' => $a, 'state_id' => $this->vig]);

        $this->cambiarEstado($a, $data + ['acta' => $this->pdf()])->assertOk()
            ->assertJsonPath('data.state.abbreviation', 'INA')->assertJsonPath('data.state_source', 'MANUAL');
        $this->assertDatabaseHas('association_status_changes', ['association_id' => $a, 'reason' => 'RENUNCIA']);
    }

    public function test_baja_por_otro_motivo_no_exige_acta(): void
    {
        $a = $this->comite($this->resolucion('VIG'), 'M2');
        $this->cambiarEstado($a, ['state_id' => $this->ina, 'reason' => 'OTRO', 'effective_date' => $this->dias(0), 'notes' => 'x'])->assertOk();
    }

    public function test_acta_que_no_es_pdf_se_rechaza(): void
    {
        $a = $this->comite($this->resolucion('VIG'), 'M3');
        $img = \Illuminate\Http\UploadedFile::fake()->image('acta.png');
        $this->cambiarEstado($a, ['state_id' => $this->ina, 'reason' => 'RENUNCIA', 'effective_date' => $this->dias(0), 'acta' => $img])
            ->assertStatus(422)->assertJsonValidationErrors('acta');
    }

    public function test_cambio_de_estado_validaciones(): void
    {
        $a = $this->comite($this->resolucion('VIG'), 'M4');
        $this->cambiarEstado($a, [])->assertStatus(422)->assertJsonValidationErrors(['state_id', 'reason', 'effective_date']);
        $this->cambiarEstado($a, ['state_id' => $this->ven, 'reason' => 'OTRO', 'effective_date' => $this->dias(0)])->assertStatus(422)->assertJsonValidationErrors('state_id');
        $this->cambiarEstado($a, ['state_id' => $this->ina, 'reason' => 'FUGA', 'effective_date' => $this->dias(0)])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->cambiarEstado($a, ['state_id' => $this->ina, 'reason' => 'OTRO', 'effective_date' => 'ayer'])->assertStatus(422)->assertJsonValidationErrors('effective_date');
    }

    public function test_estado_manual_no_lo_pisa_el_sync_automatico_y_se_puede_restaurar(): void
    {
        $a = $this->comite($this->resolucion('VIG'), 'M5');
        $this->cambiarEstado($a, ['state_id' => $this->ina, 'reason' => 'OTRO', 'effective_date' => $this->dias(0)])->assertOk();

        $this->actingAs($this->admin())->getJson(self::COMITES.'/clubs')->assertOk();
        $this->assertDatabaseHas('associations', ['id' => $a, 'state_id' => $this->ina]);

        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$a/restore-automatic-status")
            ->assertOk()->assertJsonPath('data.state.abbreviation', 'VIG')->assertJsonPath('data.state_source', 'AUTOMATIC');
    }

    public function test_cambio_de_estado_deja_evento_de_auditoria(): void
    {
        $a = $this->comite($this->resolucion('VIG'), 'M6');
        $this->cambiarEstado($a, ['state_id' => $this->ina, 'reason' => 'OTRO', 'effective_date' => $this->dias(0)])->assertOk();
        $this->assertDatabaseHas('system_audit_events', ['event_type' => 'ASSOCIATION_STATUS_CHANGED', 'subject_id' => $a]);
    }

    // ============ ELIMINAR / LISTAR / PERMISOS ============

    public function test_eliminar_comite_vacio_si_y_con_socios_no(): void
    {
        $res = $this->resolucion('VIG');
        $vacio = $this->comite($res, 'X1');
        $lleno = $this->comite($res, 'X2');
        $this->socio($lleno);

        $this->actingAs($this->admin())->deleteJson(self::COMITES."/clubs/$vacio")->assertNoContent();
        $this->actingAs($this->admin())->deleteJson(self::COMITES."/clubs/$lleno")->assertStatus(422);
        $this->assertDatabaseHas('associations', ['id' => $lleno]);
    }

    public function test_listado_busqueda_y_filtro_por_estado(): void
    {
        $this->comite($this->resolucion('VIG'), 'L1', ['name' => 'Las Flores']);
        $this->comite($this->resolucion('VEN'), 'L2', ['name' => 'El Roble']);

        $get = fn (string $q) => $this->actingAs($this->admin())->getJson(self::COMITES.'/clubs?'.$q)->assertOk()->json('data');

        $this->assertCount(2, $get(''));
        $this->assertCount(1, $get('search=Flores'));
        $this->assertCount(1, $get('state_id='.$this->ina));
        $this->assertCount(0, $get('search=zzz'));
    }

    public function test_detalle_del_comite_muestra_socios_y_resoluciones(): void
    {
        $res = $this->resolucion('VIG');
        $a = $this->comite($res, 'DT');
        $this->socio($a);

        $this->actingAs($this->admin())->getJson(self::COMITES."/clubs/$a")
            ->assertOk()->assertJsonCount(1, 'data.partners')->assertJsonPath('data.resolution_id', $res);
    }

    public function test_comite_sin_sesion_da_401(): void
    {
        $res = $this->resolucion('VIG');
        $this->postJson(self::COMITES.'/clubs', $this->clubPayload($res, 'P2'))->assertUnauthorized();
        $this->getJson(self::COMITES.'/clubs')->assertUnauthorized();
        $this->postJson(self::COMITES.'/clubs/1/asignar-cargo', [])->assertUnauthorized();
    }

    public function test_permisos_de_comite(): void
    {
        $res = $this->resolucion('VIG');
        $a = $this->comite($res, 'P1');

        $this->actingAs($this->sinAcceso())->getJson(self::COMITES.'/clubs')->assertForbidden();
        $this->actingAs($this->lector())->getJson(self::COMITES.'/clubs')->assertOk();
        $this->actingAs($this->lector())->postJson(self::COMITES.'/clubs', $this->clubPayload($res, 'P2'))->assertForbidden();
        $this->actingAs($this->lector())->putJson(self::COMITES."/clubs/$a", ['name' => 'x'])->assertForbidden();
        $this->actingAs($this->lector())->deleteJson(self::COMITES."/clubs/$a")->assertForbidden();
        $this->actingAs($this->lector())->postJson(self::COMITES."/clubs/$a/asignar-cargo", ['partner_id' => 1, 'position' => 'PRESIDENTA'])->assertForbidden();
        $this->actingAs($this->lector())->postJson(self::COMITES."/clubs/$a/status-change", ['state_id' => $this->ina, 'reason' => 'OTRO', 'effective_date' => $this->dias(0)])->assertForbidden();
    }

    // ============ CARGOS ============

    private function comiteConSocias(int $n = 3): array
    {
        $a = $this->comite($this->resolucion('VIG'), 'K'.random_int(100, 9999));
        $socias = [];
        for ($i = 0; $i < $n; $i++) {
            $socias[] = $this->socio($a);
        }

        return [$a, $socias];
    }

    private function cargo(int $comite, int $partner, string $position)
    {
        return $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/asignar-cargo", ['partner_id' => $partner, 'position' => $position]);
    }

    private function cargos(int $comite, array $assignments)
    {
        return $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$comite/asignar-cargos", ['assignments' => $assignments]);
    }

    /** @dataProvider cargosValidos */
    public function test_asignar_cada_cargo_de_la_directiva(string $cargo): void
    {
        [$a, [$s1]] = $this->comiteConSocias(1);

        $this->cargo($a, $s1, $cargo)->assertOk()->assertJsonPath("data.current_positions.$cargo", $s1);
        $this->assertDatabaseHas('directives', ['partner_id' => $s1, 'state_id' => $this->vig]);
    }

    public static function cargosValidos(): array
    {
        return array_map(fn ($c) => [$c], ['PRESIDENTA', 'VICE PRESIDENTA', 'SECRETARIA', 'TESORERA', 'COORDINADORA', 'ALMACENERA', 'VOCAL']);
    }

    public function test_asignar_presidenta_expone_nombre_y_crea_cuenta_del_portal(): void
    {
        [$a, [$s1]] = $this->comiteConSocias(1);
        $dni = DB::table('partners')->join('people', 'people.id', '=', 'partners.person_id')->where('partners.id', $s1)->value('dni');

        $this->cargo($a, $s1, 'PRESIDENTA')->assertOk()->assertJsonPath('data.president_partner_id', $s1)->assertJsonStructure(['data' => ['president_name']]);
        $this->assertDatabaseHas('users', ['username' => $dni]);
    }

    /** @group hallazgos */
    public function test_reasignar_presidenta_vence_a_la_anterior(): void
    {
        [$a, [$s1, $s2]] = $this->comiteConSocias(2);
        $this->cargo($a, $s1, 'PRESIDENTA')->assertOk();
        $this->cargo($a, $s2, 'PRESIDENTA')->assertOk()->assertJsonPath('data.president_partner_id', $s2);

        $presidenta = DB::table('positions')->where('title', 'PRESIDENTA')->value('id');
        $this->assertSame(1, DB::table('directives')->where('position_id', $presidenta)->where('state_id', $this->vig)->count());
        $this->assertDatabaseHas('directives', ['partner_id' => $s1, 'position_id' => $presidenta, 'state_id' => $this->ven]);
    }

    public function test_una_socia_puede_tener_varios_cargos_a_la_vez(): void
    {
        [$a, [$s1]] = $this->comiteConSocias(1);
        $this->cargo($a, $s1, 'PRESIDENTA')->assertOk();
        $this->cargo($a, $s1, 'TESORERA')->assertOk()
            ->assertJsonPath('data.current_positions.PRESIDENTA', $s1)->assertJsonPath('data.current_positions.TESORERA', $s1);
    }

    public function test_cargo_a_socia_de_otro_comite_se_rechaza(): void
    {
        [$a] = $this->comiteConSocias(1);
        [, [$ajena]] = $this->comiteConSocias(1);

        $this->cargo($a, $ajena, 'PRESIDENTA')->assertStatus(422)->assertJsonPath('message', 'La socia seleccionada no pertenece a este comité');
        $this->assertDatabaseMissing('directives', ['partner_id' => $ajena]);
    }

    public function test_cargo_validaciones(): void
    {
        [$a, [$s1]] = $this->comiteConSocias(1);
        $this->cargo($a, $s1, 'FISCAL')->assertStatus(422)->assertJsonValidationErrors('position');
        $this->cargo($a, $s1, 'presidenta')->assertStatus(422);
        $this->cargo($a, 99999, 'VOCAL')->assertStatus(422)->assertJsonValidationErrors('partner_id');
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$a/asignar-cargo", [])->assertStatus(422)->assertJsonValidationErrors(['partner_id', 'position']);
        $this->actingAs($this->admin())->postJson(self::COMITES.'/clubs/9999/asignar-cargo', ['partner_id' => $s1, 'position' => 'VOCAL'])->assertNotFound();
    }

    public function test_asignar_varios_cargos_en_una_operacion(): void
    {
        [$a, [$s1, $s2, $s3]] = $this->comiteConSocias(3);

        $this->cargos($a, [
            ['position' => 'PRESIDENTA', 'partner_id' => $s1],
            ['position' => 'SECRETARIA', 'partner_id' => $s2],
            ['position' => 'TESORERA', 'partner_id' => $s3],
        ])->assertOk()
            ->assertJsonPath('data.current_positions.PRESIDENTA', $s1)
            ->assertJsonPath('data.current_positions.SECRETARIA', $s2)
            ->assertJsonPath('data.current_positions.TESORERA', $s3);
    }

    public function test_asignar_los_siete_cargos_de_golpe(): void
    {
        [$a, $socias] = $this->comiteConSocias(7);
        $cargos = ['PRESIDENTA', 'VICE PRESIDENTA', 'SECRETARIA', 'TESORERA', 'COORDINADORA', 'ALMACENERA', 'VOCAL'];
        $items = array_map(fn ($c, $partner) => ['position' => $c, 'partner_id' => $partner], $cargos, $socias);
        $this->cargos($a, $items)->assertOk();
        $this->assertSame(7, DB::table('directives')->whereIn('partner_id', $socias)->where('state_id', $this->vig)->count());
    }

    public function test_varios_cargos_rechaza_repetidos_vacio_y_exceso(): void
    {
        [$a, [$s1, $s2]] = $this->comiteConSocias(2);

        $this->cargos($a, [['position' => 'VOCAL', 'partner_id' => $s1], ['position' => 'VOCAL', 'partner_id' => $s2]])->assertStatus(422);
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$a/asignar-cargos", ['assignments' => []])->assertStatus(422);
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/$a/asignar-cargos", [])->assertStatus(422);
        $this->cargos($a, array_fill(0, 8, ['position' => 'VOCAL', 'partner_id' => $s1]))->assertStatus(422);
    }

    public function test_varios_cargos_es_atomico_si_una_socia_es_ajena(): void
    {
        [$a, [$s1]] = $this->comiteConSocias(1);
        [, [$ajena]] = $this->comiteConSocias(1);

        $this->cargos($a, [['position' => 'PRESIDENTA', 'partner_id' => $s1], ['position' => 'TESORERA', 'partner_id' => $ajena]])->assertStatus(422);
        $this->assertDatabaseMissing('directives', ['partner_id' => $s1]);
    }

    public function test_cargos_con_resolucion_pendiente_funcionan(): void
    {
        $a = $this->comite($this->resolucion('PEN'), 'PEN1');
        $s = $this->socio($a);
        $this->cargo($a, $s, 'PRESIDENTA')->assertOk();
    }

    public function test_directiva_se_vincula_a_la_resolucion_vigente_del_comite(): void
    {
        $res = $this->resolucion('VIG');
        $a = $this->comite($res, 'RV1');
        $s = $this->socio($a);
        $this->cargo($a, $s, 'VOCAL')->assertOk();
        $this->assertDatabaseHas('directives', ['partner_id' => $s, 'resolution_id' => $res]);
    }

    /** @group hallazgos */
    public function test_cargo_a_socia_con_estado_vencido_dentro_del_comite(): void
    {
        // Documenta el comportamiento: ¿se puede nombrar directiva a una socia con ficha VEN?
        $a = $this->comite($this->resolucion('VIG'), 'SV1');
        $s = $this->socio($a);
        DB::table('partners')->where('id', $s)->update(['state_id' => $this->ven, 'date_end' => $this->dias(-5)]);

        $this->cargo($a, $s, 'PRESIDENTA')->assertStatus(422);
    }
}
