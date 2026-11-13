<?php

namespace Tests\Feature\FlujoComites;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\FlujoComiteFixtures;

/** Paso 3 del flujo: personas, socios (todas las condiciones socioeconómicas) y beneficiarios. */
class SociosBeneficiariosFlujoTest extends TestCase
{
    use RefreshDatabase;
    use FlujoComiteFixtures;

    private int $comite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlujo();
        $this->comite = $this->comite($this->resolucion('VIG'), 'S001');
    }

    private function registrar(array $payload)
    {
        return $this->actingAs($this->admin())->postJson(self::SOCIOS.'/partners', $payload);
    }

    private function ficha(array $beneficiaries, array $over = [], ?int $personId = null)
    {
        return $this->socioPayload($personId ?? $this->persona(), $this->comite, $beneficiaries, $over);
    }

    // ============ PERSONAS ============

    private function personaPayload(array $over = []): array
    {
        return array_merge([
            'names' => 'Rosa', 'father_lastname' => 'Quispe', 'mother_lastname' => 'Mamani',
            'dni' => '71234567', 'birthdate' => '1990-05-05', 'gender' => 'F', 'address' => 'Jr. Lima 1',
        ], $over);
    }

    public function test_persona_se_registra_actualiza_y_elimina(): void
    {
        $id = $this->actingAs($this->admin())->postJson(self::SOCIOS.'/personas', $this->personaPayload())->assertCreated()->json('data.id');
        $this->actingAs($this->admin())->putJson(self::SOCIOS."/personas/$id", ['names' => 'Rosa María'])->assertOk();
        $this->assertDatabaseHas('people', ['id' => $id, 'names' => 'Rosa María', 'dni' => '71234567']);
        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/personas/$id")->assertNoContent();
        $this->assertDatabaseMissing('people', ['id' => $id]);
    }

    /** @dataProvider personaInvalida */
    public function test_persona_validaciones(array $over, string $campo): void
    {
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/personas', $this->personaPayload($over))
            ->assertStatus(422)->assertJsonValidationErrors($campo);
    }

    public static function personaInvalida(): array
    {
        return [
            [['names' => ''], 'names'], [['father_lastname' => ''], 'father_lastname'], [['mother_lastname' => ''], 'mother_lastname'],
            [['dni' => ''], 'dni'], [['dni' => '123456789'], 'dni'], [['gender' => 'X'], 'gender'],
            [['birthdate' => 'nunca'], 'birthdate'],
        ];
    }

    public function test_persona_dni_duplicado_se_rechaza_tanto_al_crear_como_al_editar(): void
    {
        $a = $this->persona(['dni' => '70000001']);
        $b = $this->persona(['dni' => '70000002']);

        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/personas', $this->personaPayload(['dni' => '70000001']))->assertStatus(422)->assertJsonValidationErrors('dni');
        $this->actingAs($this->admin())->putJson(self::SOCIOS."/personas/$b", ['dni' => '70000001'])->assertStatus(422);
        $this->actingAs($this->admin())->putJson(self::SOCIOS."/personas/$a", ['dni' => '70000001'])->assertOk();
    }

    /** @group hallazgos */
    public function test_dni_con_letras_se_rechaza(): void
    {
        // Regla de negocio esperada: un DNI peruano son 8 dígitos numéricos.
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/personas', $this->personaPayload(['dni' => 'ABCDEFGH']))->assertStatus(422);
    }

    public function test_persona_vinculada_a_socio_o_beneficiario_no_se_elimina(): void
    {
        $this->socio($this->comite, $p = $this->persona());
        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/personas/$p")->assertStatus(422);
        $this->assertDatabaseHas('people', ['id' => $p]);
    }

    public function test_reniec_dni_invalido_se_rechaza_sin_llamar_al_servicio(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/personas/reniec', ['dni' => '123'])->assertStatus(422);
        $this->actingAs($this->admin())->postJson(self::SOCIOS.'/personas/reniec', [])->assertStatus(422);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_listado_y_detalle_de_personas(): void
    {
        $p = $this->persona(['names' => 'Zulema', 'father_lastname' => 'Paucar']);
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/personas?search=Paucar')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->admin())->getJson(self::SOCIOS."/personas/$p")->assertOk()->assertJsonPath('data.dni', DB::table('people')->where('id', $p)->value('dni'));
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/personas/99999')->assertNotFound();
    }

    // ============ SOCIO SIN CONDICIÓN (exige beneficiario) ============

    public function test_socio_sin_condicion_con_nino_0_a_6(): void
    {
        $nino = $this->nino(3);
        $r = $this->registrar($this->ficha([$this->benef($nino, 'NI0')]))->assertCreated();
        $partner = $r->json('data.id');

        $ben = DB::table('beneficiaries')->where('partner_id', $partner)->where('person_id', $nino)->first();
        $this->assertSame(1, (int) $ben->priority);
        $this->assertDatabaseHas('beneficiary_histories', [
            'beneficiary_id' => $ben->id, 'type_benefit_id' => $this->benefits['NI0'], 'state_id' => $this->vig,
        ]);
        // NI0 vence el día anterior al 7.º cumpleaños.
        $nac = DB::table('people')->where('id', $nino)->value('birthdate');
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $ben->id, 'date_end' => \Carbon\Carbon::parse($nac)->addYears(7)->subDay()->toDateString()]);
    }

    public function test_socio_sin_condicion_sin_beneficiarios_se_rechaza(): void
    {
        $this->registrar($this->ficha([]))->assertStatus(422)->assertJsonValidationErrors('beneficiaries');
        $p = $this->ficha([]);
        unset($p['beneficiaries']);
        $this->registrar($p)->assertStatus(422)->assertJsonValidationErrors('beneficiaries');
    }

    public function test_prioridad_y_tipo_de_beneficio_se_corrigen_segun_edad_del_nino(): void
    {
        $mayor = $this->nino(9);
        $menor = $this->nino(2);
        $r = $this->registrar($this->ficha([
            // el cliente manda NI0 / prioridad 1 para un niño de 9 y NI7 / prioridad 2 para uno de 2
            $this->benef($mayor, 'NI0', ['priority' => 1]),
            $this->benef($menor, 'NI7', ['priority' => 2]),
        ]))->assertCreated();
        $partner = $r->json('data.id');

        $b9 = DB::table('beneficiaries')->where('partner_id', $partner)->where('person_id', $mayor)->first();
        $b2 = DB::table('beneficiaries')->where('partner_id', $partner)->where('person_id', $menor)->first();
        $this->assertSame(2, (int) $b9->priority);
        $this->assertSame(1, (int) $b2->priority);
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $b9->id, 'type_benefit_id' => $this->benefits['NI7']]);
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $b2->id, 'type_benefit_id' => $this->benefits['NI0']]);
    }

    public function test_nino_de_exactamente_7_anos_es_prioridad_2_y_de_6_anos_11_meses_prioridad_1(): void
    {
        $s = app(\App\Services\PartnerService::class);
        $this->assertSame(1, $s->priorityForBirthdate(now()->subYears(6)->subMonths(11)->toDateString()));
        $this->assertSame(2, $s->priorityForBirthdate(now()->subYears(7)->toDateString()));
    }

    public function test_varios_beneficiarios_hijo_nieto_apoderado(): void
    {
        $r = $this->registrar($this->ficha([
            $this->benef($this->nino(1), 'NI0', ['relationship_id' => $this->relHijo]),
            $this->benef($this->nino(4), 'NI0', ['relationship_id' => $this->relNieto]),
            $this->benef($this->nino(10), 'NI7', ['relationship_id' => $this->relApoderado]),
        ]))->assertCreated();

        $this->assertSame(3, DB::table('beneficiaries')->where('partner_id', $r->json('data.id'))->count());
    }

    public function test_beneficiario_sin_tipo_de_beneficio_se_guarda_sin_historial(): void
    {
        $n = $this->nino(3);
        $r = $this->registrar($this->ficha([$this->benef($n, null)]))->assertCreated();
        $ben = DB::table('beneficiaries')->where('partner_id', $r->json('data.id'))->first();
        $this->assertNotNull($ben);
        $this->assertDatabaseMissing('beneficiary_histories', ['beneficiary_id' => $ben->id]);
    }

    /** @dataProvider beneficiarioInvalido */
    public function test_beneficiario_validaciones(array $over, string $campo): void
    {
        $b = array_merge($this->benef($this->nino(3), 'NI0'), $over);
        $this->registrar($this->ficha([$b]))->assertStatus(422)->assertJsonValidationErrors($campo);
    }

    public static function beneficiarioInvalido(): array
    {
        return [
            [['person_id' => 999999], 'beneficiaries.0.person_id'],
            [['relationship_id' => 999999], 'beneficiaries.0.relationship_id'],
            [['priority' => 3], 'beneficiaries.0.priority'],
            [['type_benefit_id' => 999999], 'beneficiaries.0.type_benefit_id'],
            [['history_state_id' => 999999], 'beneficiaries.0.history_state_id'],
            [['reason_disqualification_id' => 999999], 'beneficiaries.0.reason_disqualification_id'],
            [['date_begin' => '2026-05-01', 'date_end' => '2026-04-01'], 'beneficiaries.0.date_end'],
        ];
    }

    public function test_beneficiario_sin_persona_o_parentesco_se_rechaza(): void
    {
        $this->registrar($this->ficha([['relationship_id' => $this->relHijo]]))->assertStatus(422)->assertJsonValidationErrors('beneficiaries.0.person_id');
        $this->registrar($this->ficha([['person_id' => $this->nino()]]))->assertStatus(422)->assertJsonValidationErrors('beneficiaries.0.relationship_id');
    }

    public function test_estado_de_historial_inactivo_no_es_aceptado_al_registrar(): void
    {
        $this->registrar($this->ficha([$this->benef($this->nino(), 'NI0', ['history_state_id' => $this->ina])]))
            ->assertStatus(422)->assertJsonValidationErrors('beneficiaries.0.history_state_id');
    }

    // ============ SOCIO: CAMPOS PROPIOS ============

    /** @dataProvider socioInvalido */
    public function test_socio_validaciones(array $over, string $campo): void
    {
        $this->registrar($this->ficha([$this->benef($this->nino(), 'NI0')], $over))->assertStatus(422)->assertJsonValidationErrors($campo);
    }

    public static function socioInvalido(): array
    {
        return [
            [['person_id' => 999999], 'person_id'],
            [['association_id' => 999999], 'association_id'],
            [['state_id' => 1], 'state_id'],   // ACT no es un estado temporal válido
            [['state_id' => 999999], 'state_id'],
            [['date_begin' => null], 'date_begin'],
            [['date_begin' => '2026-05-01', 'date_end' => '2026-04-01'], 'date_end'],
            [['children_count' => -1], 'children_count'],
            [['family_income' => -5], 'family_income'],
            [['marital_status' => str_repeat('x', 51)], 'marital_status'],
            [['socioeconomic_condition' => 'RICO'], 'socioeconomic_condition'],
        ];
    }

    public function test_socio_sin_persona_ni_comite_se_rechaza(): void
    {
        $this->registrar([])->assertStatus(422)->assertJsonValidationErrors(['person_id', 'association_id', 'state_id', 'date_begin']);
    }

    public function test_socio_con_campos_opcionales_completos(): void
    {
        $r = $this->registrar($this->ficha([$this->benef($this->nino(), 'NI0')], [
            'marital_status' => 'SOLTERA', 'education_level' => 'SECUNDARIA', 'occupation' => 'COMERCIANTE',
            'children_count' => 2, 'family_income' => 850.50, 'observations' => 'Ninguna',
        ]))->assertCreated();
        $this->assertDatabaseHas('partners', ['id' => $r->json('data.id'), 'occupation' => 'COMERCIANTE', 'children_count' => 2]);
    }

    public function test_socio_con_expediente_unico_pdf_y_rechazo_de_otros_formatos(): void
    {
        $base = $this->ficha([$this->benef($this->nino(), 'NI0')]);

        $r = $this->actingAs($this->admin())->post(self::SOCIOS.'/partners', $this->aForm($base) + ['family_document' => $this->pdf()], ['Accept' => 'application/json'])->assertCreated();
        $this->assertDatabaseHas('document_attachments', ['document_type' => 'expediente_unico']);

        $base2 = $this->ficha([$this->benef($this->nino(), 'NI0')]);
        $this->actingAs($this->admin())->post(self::SOCIOS.'/partners', $this->aForm($base2) + ['family_document' => UploadedFile::fake()->image('x.png')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('family_document');
    }

    public function test_documento_de_beneficiario_se_asocia_al_beneficiario(): void
    {
        $n = $this->nino();
        $base = $this->ficha([$this->benef($n, 'NI0')]);
        $this->actingAs($this->admin())->post(self::SOCIOS.'/partners', $this->aForm($base) + ['beneficiary_documents' => [0 => $this->pdf()]], ['Accept' => 'application/json'])->assertCreated();

        $benId = DB::table('beneficiaries')->where('person_id', $n)->value('id');
        $this->assertDatabaseHas('document_attachments', ['attachable_id' => $benId, 'document_type' => 'expediente_unico']);
    }

    private function aForm(array $payload): array
    {
        return $payload; // el cliente de pruebas serializa los arreglos anidados como multipart
    }

    // ============ CONDICIONES SOCIOECONÓMICAS ============

    public function test_gestante_exige_fum_y_no_exige_beneficiarios(): void
    {
        $p = $this->socioPayload($this->persona(), $this->comite, [], ['socioeconomic_condition' => 'GESTANTE']);
        unset($p['beneficiaries']);

        $this->registrar($p)->assertStatus(422)->assertJsonValidationErrors('fum');
        $this->registrar($p + ['fum' => $this->dias(1)])->assertStatus(422)->assertJsonValidationErrors('fum');   // FUM futura

        $fum = now()->subMonths(3)->toDateString();
        $r = $this->registrar($p + ['fum' => $fum])->assertCreated();
        $partner = DB::table('partners')->find($r->json('data.id'));

        $ben = DB::table('beneficiaries')->where('partner_id', $partner->id)->where('person_id', $partner->person_id)->first();
        $this->assertNotNull($ben, 'La gestante debe quedar como beneficiaria de sí misma.');
        $this->assertSame(1, (int) $ben->priority);
        $this->assertDatabaseHas('beneficiary_histories', [
            'beneficiary_id' => $ben->id, 'type_benefit_id' => $this->benefits['GES'],
            'date_end' => \Carbon\Carbon::parse($fum)->addMonthsNoOverflow(9)->subDay()->toDateString(),
        ]);
        $hist = DB::table('beneficiary_histories')->where('beneficiary_id', $ben->id)->first();
        $this->assertDatabaseHas('obstetric_data', [
            'beneficiary_history_id' => $hist->id, 'date_last_menstrual_period' => $fum,
            'date_estimated_parturition' => \Carbon\Carbon::parse($fum)->addDays(280)->toDateString(),
        ]);
    }

    public function test_gestante_con_fum_hoy_es_valida(): void
    {
        $p = $this->socioPayload($this->persona(), $this->comite, [], ['socioeconomic_condition' => 'GESTANTE', 'fum' => now()->toDateString()]);
        unset($p['beneficiaries']);
        $this->registrar($p)->assertCreated();
    }

    public function test_lactante_exige_beneficiario_y_queda_con_lactancia_de_2_anios(): void
    {
        $this->registrar($this->ficha([], ['socioeconomic_condition' => 'LACTANTE']))->assertStatus(422)->assertJsonValidationErrors('beneficiaries');

        $bebe = $this->nino(0, 'F', ['birthdate' => now()->subMonths(4)->toDateString()]);
        $inicio = $this->dias(-30);
        $r = $this->registrar($this->ficha([$this->benef($bebe, 'NI0')], [
            'socioeconomic_condition' => 'LACTANTE', 'date_begin' => $inicio, 'benefit_child_person_id' => $bebe,
        ]))->assertCreated();

        $partner = DB::table('partners')->find($r->json('data.id'));
        $ben = DB::table('beneficiaries')->where('partner_id', $partner->id)->where('person_id', $partner->person_id)->first();
        $this->assertNotNull($ben);
        $this->assertDatabaseHas('beneficiary_histories', [
            'beneficiary_id' => $ben->id, 'type_benefit_id' => $this->benefits['LAC'],
            'date_end' => \Carbon\Carbon::parse($inicio)->addYears(2)->subDay()->toDateString(),
        ]);
    }

    public function test_benefit_child_debe_estar_entre_los_beneficiarios_de_la_ficha(): void
    {
        $bebe = $this->nino(0);
        $otro = $this->nino(2);
        $this->registrar($this->ficha([$this->benef($bebe, 'NI0')], ['socioeconomic_condition' => 'LACTANTE', 'benefit_child_person_id' => $otro]))
            ->assertStatus(422)->assertJsonValidationErrors('benefit_child_person_id');
    }

    /** @dataProvider edadesAdultoMayor */
    public function test_adulto_mayor_requiere_mas_de_65(?int $edad, bool $valido): void
    {
        $persona = $this->persona(['birthdate' => $edad === null ? null : now()->subYears($edad)->subDays(1)->toDateString()]);
        $p = $this->socioPayload($persona, $this->comite, [], ['socioeconomic_condition' => 'ADULTO_MAYOR']);
        unset($p['beneficiaries']);

        $r = $this->registrar($p);
        $valido ? $r->assertCreated() : $r->assertStatus(422)->assertJsonValidationErrors('socioeconomic_condition');
    }

    public static function edadesAdultoMayor(): array
    {
        return [[40, false], [64, false], [65, false], [66, true], [90, true], [null, false]];
    }

    public function test_adulto_mayor_valido_queda_con_beneficio_adu_prioridad_2(): void
    {
        $persona = $this->persona(['birthdate' => now()->subYears(70)->toDateString()]);
        $p = $this->socioPayload($persona, $this->comite, [], ['socioeconomic_condition' => 'ADULTO_MAYOR']);
        unset($p['beneficiaries']);
        $r = $this->registrar($p)->assertCreated();

        $ben = DB::table('beneficiaries')->where('partner_id', $r->json('data.id'))->where('person_id', $persona)->first();
        $this->assertSame(2, (int) $ben->priority);
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $ben->id, 'type_benefit_id' => $this->benefits['ADU']]);
    }

    public function test_discapacitado_exige_conadis_pdf(): void
    {
        $persona = $this->persona();
        $p = $this->socioPayload($persona, $this->comite, [$this->benef($this->nino(), 'NI0')], ['socioeconomic_condition' => 'DISCAPACITADO']);

        $this->registrar($p)->assertStatus(422)->assertJsonValidationErrors('conadis_document');

        $this->actingAs($this->admin())->post(self::SOCIOS.'/partners', $p + ['conadis_document' => UploadedFile::fake()->image('c.jpg')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('conadis_document');

        $r = $this->actingAs($this->admin())->post(self::SOCIOS.'/partners', $p + ['conadis_document' => $this->pdf('conadis.pdf')], ['Accept' => 'application/json'])->assertCreated();
        $this->assertDatabaseHas('document_attachments', ['document_type' => 'conadis']);
        $ben = DB::table('beneficiaries')->where('partner_id', $r->json('data.id'))->where('person_id', $persona)->first();
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $ben->id, 'type_benefit_id' => $this->benefits['DIS']]);
    }

    public function test_otros_no_exige_beneficiarios_y_sin_tb_no_genera_fila_de_beneficiaria(): void
    {
        $persona = $this->persona();
        $p = $this->socioPayload($persona, $this->comite, [], ['socioeconomic_condition' => 'OTROS']);
        unset($p['beneficiaries']);
        $r = $this->registrar($p)->assertCreated();
        $this->assertDatabaseMissing('beneficiaries', ['partner_id' => $r->json('data.id'), 'person_id' => $persona]);
    }

    public function test_otros_con_observacion_de_tuberculosis_genera_beneficio_tbc(): void
    {
        $persona = $this->persona();
        $p = $this->socioPayload($persona, $this->comite, [], ['socioeconomic_condition' => 'OTROS', 'observations' => 'Paciente con TBC en tratamiento']);
        unset($p['beneficiaries']);
        $r = $this->registrar($p)->assertCreated();

        $ben = DB::table('beneficiaries')->where('partner_id', $r->json('data.id'))->where('person_id', $persona)->first();
        $this->assertNotNull($ben);
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $ben->id, 'type_benefit_id' => $this->benefits['TBC']]);
    }

    // ============ REGLAS DE UNICIDAD / DUPLICADOS ============

    /** @group hallazgos */
    public function test_misma_persona_no_puede_ser_socia_dos_veces_en_el_mismo_comite(): void
    {
        $persona = $this->persona();
        $this->socio($this->comite, $persona);

        $this->registrar($this->ficha([$this->benef($this->nino(), 'NI0')], [], $persona))->assertStatus(422);
        $this->assertSame(1, DB::table('partners')->where('person_id', $persona)->count());
    }

    /** @group hallazgos */
    public function test_misma_persona_no_puede_ser_socia_vigente_en_dos_comites(): void
    {
        $otro = $this->comite($this->resolucion('VIG'), 'S002');
        $persona = $this->persona();
        $this->socio($this->comite, $persona);

        $this->registrar($this->socioPayload($persona, $otro, [$this->benef($this->nino(), 'NI0')]))->assertStatus(422);
    }

    public function test_ficha_historica_vencida_se_admite_aunque_repita_socia_y_nino(): void
    {
        $persona = $this->persona();
        $nino = $this->nino();
        $this->socio($this->comite, $persona, [$this->benef($nino, 'NI0')]);

        $this->registrar($this->socioPayload($persona, $this->comite, [$this->benef($nino, 'NI0')], [
            'state_id' => $this->ven, 'date_begin' => $this->dias(-900), 'date_end' => $this->dias(-400),
        ]))->assertCreated();
        $this->assertSame(2, DB::table('partners')->where('person_id', $persona)->count());
    }

    public function test_editar_persona_con_dni_historico_invalido_no_se_bloquea(): void
    {
        $p = $this->persona();
        DB::table('people')->where('id', $p)->update(['dni' => '1234567']);

        $this->actingAs($this->admin())->putJson(self::SOCIOS."/personas/$p", ['address' => 'Nueva dirección'])->assertOk();
        $this->actingAs($this->admin())->putJson(self::SOCIOS."/personas/$p", ['dni' => 'ABC'])->assertStatus(422);
    }

    /** @group hallazgos */
    public function test_mismo_nino_no_puede_ser_beneficiario_de_dos_socias_vigentes(): void
    {
        $nino = $this->nino();
        $this->socio($this->comite, null, [$this->benef($nino, 'NI0')]);

        $this->registrar($this->ficha([$this->benef($nino, 'NI0')]))->assertStatus(422);
    }

    /** @group hallazgos */
    public function test_mismo_nino_repetido_dentro_de_la_misma_ficha_se_rechaza(): void
    {
        $nino = $this->nino();
        $this->registrar($this->ficha([$this->benef($nino, 'NI0'), $this->benef($nino, 'NI0')]))->assertStatus(422);
    }

    /** @group hallazgos */
    public function test_socia_no_puede_ser_su_propia_beneficiaria_hijo(): void
    {
        $persona = $this->persona();
        $this->registrar($this->ficha([$this->benef($persona, 'NI0')], [], $persona))->assertStatus(422);
    }

    // ============ EDICIÓN DE SOCIO ============

    private function actualizar(int $id, array $data)
    {
        return $this->actingAs($this->admin())->putJson(self::SOCIOS."/partners/$id", $data);
    }

    public function test_editar_socio_campos_simples(): void
    {
        $s = $this->socio($this->comite);
        $this->actualizar($s, ['observations' => 'Actualizada', 'occupation' => 'AMA DE CASA'])->assertOk();
        $this->assertDatabaseHas('partners', ['id' => $s, 'observations' => 'Actualizada']);
    }

    public function test_editar_socio_sin_enviar_beneficiarios_no_los_borra(): void
    {
        $s = $this->socio($this->comite);
        $antes = DB::table('beneficiaries')->where('partner_id', $s)->count();
        $this->actualizar($s, ['observations' => 'x'])->assertOk();
        $this->assertSame($antes, DB::table('beneficiaries')->where('partner_id', $s)->count());
    }

    public function test_editar_socio_agrega_conserva_y_elimina_beneficiarios(): void
    {
        $n1 = $this->nino(2);
        $n2 = $this->nino(3);
        $s = $this->socio($this->comite, null, [$this->benef($n1, 'NI0'), $this->benef($n2, 'NI0')]);
        $ben1 = DB::table('beneficiaries')->where('partner_id', $s)->where('person_id', $n1)->value('id');
        $n3 = $this->nino(5);

        $this->actualizar($s, ['beneficiaries' => [
            ['id' => $ben1, 'person_id' => $n1, 'relationship_id' => $this->relHijo, 'type_benefit_id' => $this->benefits['NI0']],
            $this->benef($n3, 'NI0'),
        ]])->assertOk();

        $this->assertDatabaseHas('beneficiaries', ['id' => $ben1]);
        $this->assertDatabaseMissing('beneficiaries', ['partner_id' => $s, 'person_id' => $n2]);
        $this->assertDatabaseHas('beneficiaries', ['partner_id' => $s, 'person_id' => $n3]);
    }

    public function test_editar_condicion_a_gestante_crea_beneficio_ges(): void
    {
        $s = $this->socio($this->comite);
        $persona = DB::table('partners')->where('id', $s)->value('person_id');

        $this->actualizar($s, ['socioeconomic_condition' => 'GESTANTE'])->assertStatus(422)->assertJsonValidationErrors('fum');
        $this->actualizar($s, ['socioeconomic_condition' => 'GESTANTE', 'fum' => now()->subMonths(2)->toDateString()])->assertOk();

        $ben = DB::table('beneficiaries')->where('partner_id', $s)->where('person_id', $persona)->first();
        $this->assertNotNull($ben);
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $ben->id, 'type_benefit_id' => $this->benefits['GES']]);
    }

    public function test_editar_a_discapacitado_sin_conadis_se_rechaza_y_con_conadis_previo_se_acepta(): void
    {
        $s = $this->socio($this->comite);
        $this->actualizar($s, ['socioeconomic_condition' => 'DISCAPACITADO'])->assertStatus(422)->assertJsonValidationErrors('conadis_document');

        $partner = \App\Models\Partner::find($s);
        app(\App\Services\DocumentAttachmentService::class)->store($partner, 'conadis', $this->pdf());
        $this->actualizar($s, ['socioeconomic_condition' => 'DISCAPACITADO'])->assertOk();
    }

    public function test_editar_adulto_mayor_menor_de_65_se_rechaza(): void
    {
        $s = $this->socio($this->comite);
        $this->actualizar($s, ['socioeconomic_condition' => 'ADULTO_MAYOR'])->assertStatus(422);
    }

    public function test_editar_trasladar_socio_a_otro_comite(): void
    {
        $otro = $this->comite($this->resolucion('VIG'), 'S009');
        $s = $this->socio($this->comite);
        $this->actualizar($s, ['association_id' => $otro])->assertOk();
        $this->assertDatabaseHas('partners', ['id' => $s, 'association_id' => $otro]);
    }

    public function test_editar_validaciones_y_404(): void
    {
        $s = $this->socio($this->comite);
        $this->actualizar($s, ['association_id' => 9999])->assertStatus(422);
        $this->actualizar($s, ['state_id' => 1])->assertStatus(422);
        $this->actualizar($s, ['date_begin' => '2026-05-01', 'date_end' => '2026-04-01'])->assertStatus(422);
        $this->actualizar(99999, ['observations' => 'x'])->assertNotFound();
    }

    public function test_dar_de_baja_al_socio_con_fecha_fin_y_estado_vencido(): void
    {
        $s = $this->socio($this->comite);
        $this->actualizar($s, ['state_id' => $this->ven, 'date_end' => $this->dias(0)])->assertOk();
        $this->assertDatabaseHas('partners', ['id' => $s, 'state_id' => $this->ven]);
    }

    // ============ ELIMINAR SOCIO ============

    public function test_eliminar_socio_borra_beneficiarios_historial_y_documentos(): void
    {
        $s = $this->socio($this->comite);
        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/partners/$s")->assertNoContent();
        $this->assertDatabaseMissing('partners', ['id' => $s]);
        $this->assertSame(0, DB::table('beneficiaries')->where('partner_id', $s)->count());
    }

    public function test_eliminar_socio_con_directiva_asignada(): void
    {
        $s = $this->socio($this->comite);
        $this->actingAs($this->admin())->postJson(self::COMITES."/clubs/{$this->comite}/asignar-cargo", ['partner_id' => $s, 'position' => 'PRESIDENTA'])->assertOk();

        // Debe borrarse limpiamente o bloquearse con 422; nunca un 500.
        $status = $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/partners/$s")->status();
        $this->assertContains($status, [204, 422], "Eliminar socio con cargo devolvió $status");
    }

    public function test_eliminar_socio_presidenta_en_pecosa_se_bloquea(): void
    {
        $s = $this->socio($this->comite);
        $now = now();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        DB::table('pecosas')->insert([
            'pecosa_number' => 'PEC00001', 'delivery_date' => $now, 'chief_id' => 1, 'storekeeper_id' => 1,
            'president_id' => $s, 'state_id' => $this->vig, 'association_id' => $this->comite,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/partners/$s")->assertStatus(422);
        $this->assertDatabaseHas('partners', ['id' => $s]);
    }

    // ============ BENEFICIARIOS (API independiente) ============

    private function altaBeneficiario(array $over = [])
    {
        $partner = $over['partner_id'] ?? $this->socio($this->comite);

        return $this->actingAs($this->admin())->postJson(self::SOCIOS.'/beneficiarios', array_merge([
            'person_id' => $this->nino(3), 'partner_id' => $partner, 'relationship_id' => $this->relHijo,
            'type_benefit_id' => $this->benefits['NI0'], 'date_begin' => $this->dias(-5),
        ], $over));
    }

    public function test_beneficiario_nuevo_queda_vigente_con_historial(): void
    {
        $r = $this->altaBeneficiario()->assertCreated();
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $r->json('data.id'), 'state_id' => $this->vig, 'type_benefit_id' => $this->benefits['NI0']]);
    }

    public function test_beneficiario_sin_beneficio_no_crea_historial(): void
    {
        $r = $this->altaBeneficiario(['type_benefit_id' => null, 'date_begin' => null])->assertCreated();
        $this->assertDatabaseMissing('beneficiary_histories', ['beneficiary_id' => $r->json('data.id')]);
    }

    /** @dataProvider beneficiarioApiInvalido */
    public function test_beneficiario_api_validaciones(array $over, string $campo): void
    {
        $this->altaBeneficiario($over)->assertStatus(422)->assertJsonValidationErrors($campo);
    }

    public static function beneficiarioApiInvalido(): array
    {
        return [[['person_id' => 99999], 'person_id'], [['partner_id' => 99999], 'partner_id'], [['relationship_id' => 99999], 'relationship_id'], [['priority' => 5], 'priority']];
    }

    /** @group hallazgos */
    public function test_beneficiario_api_prioridad_segun_edad(): void
    {
        // Regla del flujo de ficha: 0-6 años prioridad 1, mayor de 6 prioridad 2.
        $r = $this->altaBeneficiario(['person_id' => $this->nino(10), 'type_benefit_id' => $this->benefits['NI7'], 'priority' => 1])->assertCreated();
        $this->assertDatabaseHas('beneficiaries', ['id' => $r->json('data.id'), 'priority' => 2]);
    }

    /** @group hallazgos */
    public function test_beneficiario_api_duplicado_en_la_misma_socia(): void
    {
        $partner = $this->socio($this->comite);
        $nino = $this->nino();
        $this->altaBeneficiario(['partner_id' => $partner, 'person_id' => $nino])->assertCreated();
        $this->altaBeneficiario(['partner_id' => $partner, 'person_id' => $nino])->assertStatus(422);
    }

    public function test_editar_beneficiario_cambia_parentesco(): void
    {
        $id = $this->altaBeneficiario()->assertCreated()->json('data.id');
        $b = DB::table('beneficiaries')->find($id);
        $this->actingAs($this->admin())->putJson(self::SOCIOS."/beneficiarios/$id", [
            'person_id' => $b->person_id, 'partner_id' => $b->partner_id, 'relationship_id' => $this->relNieto,
        ])->assertOk();
        $this->assertDatabaseHas('beneficiaries', ['id' => $id, 'relationship_id' => $this->relNieto]);
    }

    public function test_baja_de_beneficiario_con_historial_vigente_cierra_el_periodo(): void
    {
        $id = $this->altaBeneficiario()->assertCreated()->json('data.id');
        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/beneficiarios/$id")->assertNoContent();

        $this->assertDatabaseHas('beneficiaries', ['id' => $id]);
        $this->assertDatabaseHas('beneficiary_histories', ['beneficiary_id' => $id, 'date_end' => now()->toDateString(), 'state_id' => $this->ven]);
    }

    public function test_baja_de_beneficiario_sin_historial_lo_elimina(): void
    {
        $id = $this->altaBeneficiario(['type_benefit_id' => null, 'date_begin' => null])->assertCreated()->json('data.id');
        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/beneficiarios/$id")->assertNoContent();
        $this->assertDatabaseMissing('beneficiaries', ['id' => $id]);
    }

    public function test_baja_de_beneficiario_con_solo_historial_pasado_se_rechaza(): void
    {
        $id = $this->altaBeneficiario()->assertCreated()->json('data.id');
        DB::table('beneficiary_histories')->where('beneficiary_id', $id)->update(['date_begin' => $this->dias(-400), 'date_end' => $this->dias(-100)]);

        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/beneficiarios/$id")->assertStatus(422);
        $this->assertDatabaseHas('beneficiaries', ['id' => $id]);
    }

    public function test_detalle_listado_y_opciones_de_beneficiarios(): void
    {
        $id = $this->altaBeneficiario()->assertCreated()->json('data.id');
        $this->actingAs($this->admin())->getJson(self::SOCIOS."/beneficiarios/$id")->assertOk();
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/beneficiarios?year='.now()->year.'&month='.now()->month)->assertOk()->assertJsonPath('period.year', now()->year);
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/beneficiarios/options')->assertOk();
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/beneficiarios/99999')->assertNotFound();
    }

    public function test_listado_de_socios_opciones_y_filtros(): void
    {
        $this->socio($this->comite);
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/partners?association_id='.$this->comite)->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/partners?association_id=9999')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->admin())->getJson(self::SOCIOS.'/partners/options')->assertOk()->assertJsonStructure(['associations', 'states', 'relationships', 'type_benefits']);
    }

    // ============ DOCUMENTOS ============

    public function test_documentos_subir_descargar_y_borrar(): void
    {
        $s = $this->socio($this->comite);
        $id = $this->actingAs($this->admin())->post(self::SOCIOS.'/documents', [
            'attachable_type' => 'partner', 'attachable_id' => $s, 'document_type' => 'expediente_unico', 'file' => $this->pdf(),
        ], ['Accept' => 'application/json'])->assertCreated()->json('id');

        $this->actingAs($this->admin())->get(self::SOCIOS."/documents/$id")->assertOk();
        $this->actingAs($this->admin())->deleteJson(self::SOCIOS."/documents/$id")->assertNoContent();
    }

    public function test_documento_con_tipo_o_entidad_invalida_se_rechaza(): void
    {
        $s = $this->socio($this->comite);
        $this->actingAs($this->admin())->post(self::SOCIOS.'/documents', ['attachable_type' => 'user', 'attachable_id' => $s, 'document_type' => 'conadis', 'file' => $this->pdf()], ['Accept' => 'application/json'])->assertStatus(422);
        $this->actingAs($this->admin())->post(self::SOCIOS.'/documents', ['attachable_type' => 'partner', 'attachable_id' => $s, 'document_type' => 'otro', 'file' => $this->pdf()], ['Accept' => 'application/json'])->assertStatus(422);
        $this->actingAs($this->admin())->post(self::SOCIOS.'/documents', ['attachable_type' => 'partner', 'attachable_id' => $s, 'document_type' => 'conadis', 'file' => UploadedFile::fake()->image('a.png')], ['Accept' => 'application/json'])->assertStatus(422);
    }

    // ============ PERMISOS ============

    public function test_permisos_del_modulo_socios(): void
    {
        $s = $this->socio($this->comite);
        $payload = $this->ficha([$this->benef($this->nino(), 'NI0')]);

        $this->actingAs($this->sinAcceso())->getJson(self::SOCIOS.'/partners')->assertForbidden();
        $this->actingAs($this->lector())->getJson(self::SOCIOS.'/partners')->assertOk();
        $this->actingAs($this->lector())->postJson(self::SOCIOS.'/partners', $payload)->assertForbidden();
        $this->actingAs($this->lector())->putJson(self::SOCIOS."/partners/$s", ['observations' => 'x'])->assertForbidden();
        $this->actingAs($this->lector())->deleteJson(self::SOCIOS."/partners/$s")->assertForbidden();
        $this->actingAs($this->lector())->postJson(self::SOCIOS.'/personas', $this->personaPayload())->assertForbidden();
        $this->actingAs($this->lector())->postJson(self::SOCIOS.'/beneficiarios', [])->assertForbidden();
        $this->assertDatabaseHas('partners', ['id' => $s]);
    }

    public function test_socios_sin_sesion_da_401(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson(self::SOCIOS.'/partners')->assertUnauthorized();
        $this->postJson(self::SOCIOS.'/partners', [])->assertUnauthorized();
    }

    // ============ RUTAS WEB (formularios clásicos) ============

    public function test_web_crear_socio_con_beneficiarios(): void
    {
        $persona = $this->persona();
        $nino = $this->nino();
        $this->actingAs($this->admin())->post('/socios-beneficiarios/socios', $this->socioPayload($persona, $this->comite, [$this->benef($nino, 'NI0')]))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('partners', ['person_id' => $persona]);
        $this->assertDatabaseHas('beneficiaries', ['person_id' => $nino]);
    }

    public function test_web_crear_socio_guarda_el_expediente_adjunto(): void
    {
        $persona = $this->persona();
        $this->actingAs($this->admin())->post('/socios-beneficiarios/socios', $this->socioPayload($persona, $this->comite, [$this->benef($this->nino(), 'NI0')]) + ['family_document' => $this->pdf()])
            ->assertRedirect(route('dashboard'));
        $partner = DB::table('partners')->where('person_id', $persona)->value('id');
        $this->assertSame(1, DB::table('document_attachments')->where('attachable_id', $partner)->count());
    }

    public function test_web_crear_socio_invalido_vuelve_con_errores(): void
    {
        $this->actingAs($this->admin())->from('/dashboard')->post('/socios-beneficiarios/socios', [])->assertSessionHasErrors(['person_id', 'association_id']);
    }

    public function test_web_crud_persona_y_beneficiario(): void
    {
        $this->actingAs($this->admin())->post('/socios-beneficiarios/personas', $this->personaPayload(['dni' => '70707070']))->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('people', ['dni' => '70707070']);

        $s = $this->socio($this->comite);
        $nino = $this->nino();
        $this->actingAs($this->admin())->post('/socios-beneficiarios/beneficiarios', ['person_id' => $nino, 'partner_id' => $s, 'relationship_id' => $this->relHijo])->assertRedirect(route('dashboard'));
        $id = DB::table('beneficiaries')->where('person_id', $nino)->value('id');
        $this->actingAs($this->admin())->put("/socios-beneficiarios/beneficiarios/$id", ['person_id' => $nino, 'partner_id' => $s, 'relationship_id' => $this->relNieto])->assertRedirect(route('dashboard'));
        $this->actingAs($this->admin())->delete("/socios-beneficiarios/beneficiarios/$id")->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('beneficiaries', ['id' => $id]);
    }

    public function test_fichas_pdf_de_socio(): void
    {
        $s = $this->socio($this->comite);
        $this->actingAs($this->admin())->get("/socios-beneficiarios/socios/$s/ficha")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->admin())->get('/socios-beneficiarios/beneficiarios-imprimir')->assertOk();
    }
}
