<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Beneficiarie;
use App\Models\Association;
use App\Models\Pecosa;
use App\Models\Directive;
use App\Models\State;
use App\Models\People;
use App\Models\Relationship;
use App\Models\TypeBenefit;
use App\Models\ReasonDisqualification;
use App\Models\PlaceSector;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SociosBeneficiariosController extends Controller
{
    // ==================== ÍNDICE PRINCIPAL ====================

    public function index(Request $request)
    {
        $query = Partner::with(['people', 'association', 'state']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('people', function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                    ->orWhere('father_lastname', 'like', "%{$search}%")
                    ->orWhere('mother_lastname', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->has('association_id') && $request->association_id != '') {
            $query->where('association_id', $request->association_id);
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        $partners = $query->with('beneficiaries.person', 'beneficiaries.relationship', 'beneficiaries.histories')->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        $states = State::all();
        $people = People::whereDoesntHave('partners')->get();
        $allPeople = People::all();
        $relationships = Relationship::all();
        $placeSectors = PlaceSector::with(['place', 'sector'])->get();
        $typeBenefits = \App\Models\TypeBenefit::all();
        $reasonDisqualifications = \App\Models\ReasonDisqualification::all();

        return view('socios-beneficiarios.index', compact('partners', 'associations', 'states', 'people', 'allPeople', 'relationships', 'placeSectors', 'typeBenefits', 'reasonDisqualifications'));
    }

    // ==================== PERSONAS ====================

    public function indexPersonas(Request $request)
    {
        $query = People::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('names', 'like', "%{$search}%")
                ->orWhere('father_lastname', 'like', "%{$search}%")
                ->orWhere('mother_lastname', 'like', "%{$search}%")
                ->orWhere('dni', 'like', "%{$search}%");
        }

        $people = $query->orderBy('id', 'desc')->paginate(15);
        $placeSectors = PlaceSector::with(['place', 'sector'])->get();
        return view('socios-beneficiarios.personas.index', compact('people', 'placeSectors'));
    }

    public function storePersona(Request $request)
    {
        $validated = $request->validate([
            'names' => 'required|string|max:255',
            'father_lastname' => 'required|string|max:255',
            'mother_lastname' => 'required|string|max:255',
            'dni' => 'required|string|size:8|unique:people,dni',
            'birthdate' => 'required|date',
            'gender' => 'required|in:M,F',
            'telephone_number' => 'nullable|string|max:6',
            'phone_number' => 'nullable|string|max:9',
            'address' => 'required|string|max:500',
            'place_sector_id' => 'required|exists:place_sectors,id',
        ]);

        People::create($validated);
        return redirect()->route('socios-beneficiarios.personas.index')->with('success', 'Persona registrada exitosamente');
    }

    public function updatePersona(Request $request, People $person)
    {
        $validated = $request->validate([
            'names' => 'required|string|max:255',
            'father_lastname' => 'required|string|max:255',
            'mother_lastname' => 'required|string|max:255',
            'dni' => 'required|string|size:8|unique:people,dni,' . $person->id,
            'birthdate' => 'required|date',
            'gender' => 'required|in:M,F',
            'telephone_number' => 'nullable|string|max:6',
            'phone_number' => 'nullable|string|max:9',
            'address' => 'required|string|max:500',
            'place_sector_id' => 'required|exists:place_sectors,id',
        ]);

        $person->update($validated);
        return redirect()->route('socios-beneficiarios.personas.index')->with('success', 'Persona actualizada exitosamente');
    }

    public function destroyPersona(People $person)
    {
        $hasPartners = $person->partners()->exists();
        $hasBeneficiaries = $person->beneficiaries()->exists();

        if ($hasPartners || $hasBeneficiaries) {
            return redirect()->route('socios-beneficiarios.personas.index')->with('error', 'No se puede eliminar la persona porque está asociada a un socio o beneficiario');
        }

        $person->delete();
        return redirect()->route('socios-beneficiarios.personas.index')->with('success', 'Persona eliminada exitosamente');
    }

    // ==================== SOCIOS ====================

    public function storeSocio(Request $request)
    {
        if ($request->has('create_person') && $request->create_person == '1') {
            $personData = $request->validate([
                'names' => 'required|string|max:255',
                'father_lastname' => 'required|string|max:255',
                'mother_lastname' => 'required|string|max:255',
                'dni' => 'required|string|size:8|unique:people,dni',
                'birthdate' => 'required|date',
                'gender' => 'required|in:M,F',
                'address' => 'required|string|max:500',
                'place_sector_id' => 'required|exists:place_sectors,id',
            ]);

            $person = People::create($personData);
            $request->merge(['person_id' => $person->id]);
        }

        $validated = $request->validate([
            'date_begin' => 'required|date',
            'date_end' => 'required|date',
            'observations' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'person_id' => 'required|exists:people,id',
            'association_id' => 'required|exists:associations,id',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.person_id' => 'required_with:beneficiaries|exists:people,id',
            'beneficiaries.*.relationship_id' => 'required_with:beneficiaries|exists:relationships,id',
            'beneficiaries.*.weight' => 'nullable|numeric|min:0',
            'beneficiaries.*.height' => 'nullable|numeric|min:0',
            'beneficiaries.*.hmg' => 'nullable|numeric|min:0',
            'beneficiaries.*.date_begin' => 'nullable|date',
            'beneficiaries.*.date_end' => 'nullable|date',
            'beneficiaries.*.type_benefit_id' => 'nullable|exists:type_benefits,id',
            'beneficiaries.*.history_state_id' => 'nullable|exists:states,id',
            'beneficiaries.*.reason_disqualification_id' => 'nullable|exists:reason_disqualifications,id',
        ]);

        $partner = Partner::create($validated);

        if ($request->has('beneficiaries') && !empty($request->beneficiaries)) {
            foreach ($request->beneficiaries as $beneficiary) {
                if (!empty($beneficiary['person_id']) && !empty($beneficiary['relationship_id'])) {
                    $ben = Beneficiarie::create([
                        'person_id' => $beneficiary['person_id'],
                        'partner_id' => $partner->id,
                        'relationship_id' => $beneficiary['relationship_id'],
                    ]);

                    // Guardar historial si se proporcionaron datos
                    if (!empty($beneficiary['type_benefit_id']) && !empty($beneficiary['history_state_id'])
                        && !empty($beneficiary['date_begin']) && !empty($beneficiary['date_end'])) {
                        \App\Models\BeneficiaryHistory::create([
                            'weight' => $beneficiary['weight'] ?? 0,
                            'height' => $beneficiary['height'] ?? 0,
                            'hmg' => $beneficiary['hmg'] ?? 0,
                            'date_begin' => $beneficiary['date_begin'],
                            'date_end' => $beneficiary['date_end'],
                            'type_benefit_id' => $beneficiary['type_benefit_id'],
                            'beneficiary_id' => $ben->id,
                            'state_id' => $beneficiary['history_state_id'],
                            'reason_disqualification_id' => $beneficiary['reason_disqualification_id'] ?? null,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('socios-beneficiarios.socios.index')->with('success', 'Socio y beneficiarios creados exitosamente');
    }

    public function updateSocio(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'date_begin' => 'required|date',
            'date_end' => 'required|date',
            'observations' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'person_id' => 'required|exists:people,id',
            'association_id' => 'required|exists:associations,id',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.person_id' => 'required_with:beneficiaries|exists:people,id',
            'beneficiaries.*.relationship_id' => 'required_with:beneficiaries|exists:relationships,id',
        ]);

        $partner->update($validated);

        if ($request->has('beneficiaries')) {
            $partner->beneficiaries()->delete();

            foreach ($request->beneficiaries as $beneficiary) {
                if (!empty($beneficiary['person_id']) && !empty($beneficiary['relationship_id'])) {
                    Beneficiarie::create([
                        'person_id' => $beneficiary['person_id'],
                        'partner_id' => $partner->id,
                        'relationship_id' => $beneficiary['relationship_id'],
                    ]);
                }
            }
        }

        return redirect()->route('socios-beneficiarios.socios.index')->with('success', 'Socio y beneficiarios actualizados exitosamente');
    }

    public function destroySocio(Partner $partner)
    {
        $partner->beneficiaries()->delete();
        $partner->delete();
        return redirect()->route('socios-beneficiarios.socios.index')->with('success', 'Socio y beneficiarios eliminados exitosamente');
    }

    // ==================== BENEFICIARIOS ====================

    public function indexBeneficiarios(Request $request)
    {
        $query = Beneficiarie::with(['person', 'partner', 'relationship']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('person', function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                    ->orWhere('father_lastname', 'like', "%{$search}%")
                    ->orWhere('mother_lastname', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->has('partner_id') && $request->partner_id != '') {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->has('relationship_id') && $request->relationship_id != '') {
            $query->where('relationship_id', $request->relationship_id);
        }

        $beneficiaries = $query->orderBy('id', 'desc')->paginate(10);
        $partners = Partner::with('people')->get();
        $relationships = Relationship::all();
        $people = People::all();

        return view('socios-beneficiarios.beneficiarios.index', compact('beneficiaries', 'partners', 'relationships', 'people'));
    }


    // ==================== REPORTES ====================

    /**
     * Reporte Padrón de Beneficiarios del Club de Madres PVL
     * Filtrado por comité, mes y año.
     * Basado en reporte_beneficiario.blade.php
     */
    public function reportePadronBeneficiarios(Request $request)
    {
        $associations = Association::all();
        $associationId = $request->get('association_id');
        $mes = $request->get('month', date('n'));
        $anio = $request->get('year', date('Y'));

        if (!$associationId) {
            return view('socios-beneficiarios.beneficiarios.padron-filtros', compact('associations', 'mes', 'anio'));
        }

        $association = Association::with(['placeSector.place', 'placeSector.sector'])->findOrFail($associationId);
        $presidenta = $association->getPresidentName();
        
        $startDate = "$anio-$mes-01";
        $endDate = "$anio-$mes-" . date('t', strtotime($startDate));

        $partners = Partner::with(['people', 'beneficiaries.person', 'beneficiaries.relationship', 'beneficiaries.histories.typeBenefit', 'beneficiaries.histories.reasonDisqualification'])
            ->where('association_id', $associationId)
            ->get();

        // Debug: ver si hay datos
        if ($partners->isEmpty()) {
            dd('No hay partners para association_id: ' . $associationId);
        }

        // Obtener PECOSA del periodo (mes/año) para este comité
        $pecosa = Pecosa::with('detailPecosas.detailProduct.product')
            ->where('association_id', $associationId)
            ->whereMonth('delivery_date', $mes)
            ->whereYear('delivery_date', $anio)
            ->first();

        // Construir array de beneficiarios para la vista
        $beneficiarios = [];
        $resumen = [
            'total' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'masculino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'femenino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
        ];

        foreach ($partners as $partner) {
            $socia = $partner->people;
            if (!$socia) continue;

            $sociaEdad = $socia->birthdate ? Carbon::parse($socia->birthdate)->age : 0;
            $sociaEdadMeses = $socia->birthdate ? Carbon::parse($socia->birthdate)->diff(Carbon::now())->m : 0;

            $beneficiariosSocia = [];

            foreach ($partner->beneficiaries as $beneficiario) {
                $persona = $beneficiario->person;
                if (!$persona) continue;

                $edadAnos = $persona->birthdate ? Carbon::parse($persona->birthdate)->age : 0;
                $edadMeses = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff(Carbon::now())->m : 0;
                $edadDias = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff(Carbon::now())->d : 0;

                // Obtener el historial activo (más reciente)
                $historialActivo = $beneficiario->histories()
                    ->whereNotNull('state_id')
                    ->orderByDesc('date_begin')
                    ->first();

                $tipoBeneficio = $historialActivo && $historialActivo->typeBenefit ? $historialActivo->typeBenefit->abbreviation : '';
                $razonBaja = $historialActivo && $historialActivo->reasonDisqualification ? $historialActivo->reasonDisqualification->id : '';
                $tipoVisible = in_array($tipoBeneficio, ['LAC', 'GES']) ? $tipoBeneficio : '';
                $parentescoTitulo = $beneficiario->relationship ? $beneficiario->relationship->title : '';
                
                $fechaInicio = $historialActivo && $historialActivo->date_begin ? $historialActivo->date_begin : null;
                $observationFlag = false;
                $bajaFlag = false;
                
                // Lógica para actualizar reason_disqualification y marcar observaciones
                
                // 1. Si tiene 14 años o más → reason_disqualification_id = 4 (BAJA)
                if ($edadAnos >= 14) {
                    if ($historialActivo && !$historialActivo->reason_disqualification_id) {
                        $historialActivo->update(['reason_disqualification_id' => 4]);
                        $razonBaja = 4;
                        $bajaFlag = true;
                    }
                }
                
                // 2. Si el tipo es GES y ya está más de 9 meses o no tiene fecha de ingreso → reason_disqualification_id = 4 (BAJA)
                if ($tipoBeneficio === 'GES') {
                    $mesesTotales = $fechaInicio ? Carbon::parse($fechaInicio)->diffInMonths(Carbon::now()) : 999;
                    if (!$fechaInicio || $mesesTotales > 9) {
                        if ($historialActivo && (!$historialActivo->reason_disqualification_id || $historialActivo->reason_disqualification_id == 1)) {
                            $historialActivo->update(['reason_disqualification_id' => 4]);
                            $razonBaja = 4;
                            $bajaFlag = true;
                        }
                    }
                }
                
                // 3. Si el tipo es LAC y ya está más de un año o no tiene fecha de ingreso → reason_disqualification_id = 4 (BAJA)
                if ($tipoBeneficio === 'LAC') {
                    $mesesTotales = $fechaInicio ? Carbon::parse($fechaInicio)->diffInMonths(Carbon::now()) : 999;
                    if (!$fechaInicio || $mesesTotales > 12) {
                        if ($historialActivo && (!$historialActivo->reason_disqualification_id || $historialActivo->reason_disqualification_id == 1)) {
                            $historialActivo->update(['reason_disqualification_id' => 4]);
                            $razonBaja = 4;
                            $bajaFlag = true;
                        }
                    }
                }
                
                // 4. Si GES/LAC tiene 12 años o menos → solo observación (no se cambia reason_disqualification)
                if (in_array($tipoBeneficio, ['GES', 'LAC']) && $edadAnos <= 12) {
                    $observationFlag = true;
                }

                $beneficiariosSocia[] = [
                    'beneficiario_nombre' => strtoupper($persona->father_lastname . ' ' . $persona->mother_lastname . ' ' . $persona->names),
                    'beneficiario_dni' => $persona->dni ?? '',
                    'beneficiario_baja' => (!empty($razonBaja) && $razonBaja != 1) ? '1' : '',
                    'beneficiario_tipo' => $tipoVisible,
                    'beneficiario_fecha_nacimiento' => $persona->birthdate ? date('d/m/Y', strtotime($persona->birthdate)) : '',
                    'beneficiario_sexo' => $persona->gender === 'M' ? 'M' : 'F',
                    'beneficiario_parentesco' => $parentescoTitulo,
                    'beneficiario_edad_anos' => $edadAnos,
                    'beneficiario_edad_meses' => $edadMeses,
                    'beneficiario_edad_dias' => $edadDias,
                    'historial' => $historialActivo,
                    'es_baja' => (!empty($razonBaja) && $razonBaja != 1),
                    'observation' => $observationFlag,
                    'fecha_inicio' => $fechaInicio ? date('d/m/Y', strtotime($fechaInicio)) : '',
                ];

                // Resumen por prioridad y tipo, contado por beneficiario
                if ($edadAnos <= 6) {
                    $key = $edadAnos == 1 ? '1_ano' : $edadAnos . '_anos';
                    $resumen['total'][$key]++;
                    $resumen['total']['total']++;

                    if ($persona->gender === 'M') {
                        $resumen['masculino'][$key]++;
                        $resumen['masculino']['total']++;
                    } else {
                        $resumen['femenino'][$key]++;
                        $resumen['femenino']['total']++;
                    }
                }

                if ($edadAnos >= 7 && $edadAnos <= 13) {
                    $resumen['total']['ninos_7_13']++;

                    if ($persona->gender === 'M') {
                        $resumen['masculino']['ninos_7_13']++;
                    } else {
                        $resumen['femenino']['ninos_7_13']++;
                    }
                }

                if ($tipoBeneficio === 'GES') {
                    $resumen['total']['madres_gestantes']++;

                    if ($persona->gender === 'M') {
                        $resumen['masculino']['madres_gestantes']++;
                    } else {
                        $resumen['femenino']['madres_gestantes']++;
                    }
                }

                if ($tipoBeneficio === 'LAC') {
                    $resumen['total']['madres_lactantes']++;

                    if ($persona->gender === 'M') {
                        $resumen['masculino']['madres_lactantes']++;
                    } else {
                        $resumen['femenino']['madres_lactantes']++;
                    }
                }

                if ($tipoBeneficio === 'ADU') {
                    $resumen['total']['ancianos']++;

                    if ($persona->gender === 'M') {
                        $resumen['masculino']['ancianos']++;
                    } else {
                        $resumen['femenino']['ancianos']++;
                    }
                }

                if ($tipoBeneficio === 'TBC') {
                    $resumen['total']['tuberculosos']++;

                    if ($persona->gender === 'M') {
                        $resumen['masculino']['tuberculosos']++;
                    } else {
                        $resumen['femenino']['tuberculosos']++;
                    }
                }

                // Contar bajas (reason_disqualification_id != 1)
                if (!empty($razonBaja) && $razonBaja != 1) {
                    $resumen['total']['gap']++;
                    if ($persona->gender === 'M') {
                        $resumen['masculino']['gap']++;
                    } else {
                        $resumen['femenino']['gap']++;
                    }
                }

                // Total general (todos los beneficiarios únicos)
                $resumen['total']['total_general']++;
                if ($persona->gender === 'M') {
                    $resumen['masculino']['total_general']++;
                } else {
                    $resumen['femenino']['total_general']++;
                }
            }

            if (!empty($beneficiariosSocia)) {
                $beneficiarios[] = [
                    'socia_nombre' => strtoupper($socia->father_lastname . ' ' . $socia->mother_lastname . ' ' . $socia->names),
                    'socia_direccion' => $socia->address ?? '',
                    'socia_dni' => $socia->dni ?? '',
                    'rowspan' => count($beneficiariosSocia),
                    'items' => $beneficiariosSocia,
                ];
            }
        }

        $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $periodo = $anio . '-' . ($mes <= 6 ? 'I' : 'II');
        $resumenFilas = [
            [
                'label' => 'MASCULINO',
                'data' => $resumen['masculino'],
            ],
            [
                'label' => 'FEMENINO',
                'data' => $resumen['femenino'],
            ],
            [
                'label' => 'TOTAL',
                'data' => $resumen['total'],
            ],
        ];

        /**
         * Cálculo de observaciones para el resumen de auditoría
         * 
         * Observaciones BAJA (reason_disqualification_id != 1):
         * - EDAD >= 14 años
         * - GES. MAS DE 9 MESES / SIN FECHA DE INGRESO
         * - LAC. MAS DE UN AÑO / SIN FECHA INGRESO
         * 
         * Otras observaciones:
         * - ANCIANO < DE 65 AÑOS
         * - GES / LAC <= DE 12 AÑOS

         */
        $observaciones = [];
        
        // Obtener todos los beneficiarios aplanados para análisis
        $todosBeneficiarios = [];
        foreach ($beneficiarios as $grupo) {
            foreach ($grupo['items'] as $item) {
                $todosBeneficiarios[] = $item;
            }
        }

        // 1. EDAD >= 14 años (BAJA) - cuenta todos con reason_disqualification_id != 1
        $cantidadEdadBaja = 0;
        foreach ($todosBeneficiarios as $ben) {
            if (!empty($ben['beneficiario_baja']) && $ben['beneficiario_baja'] != 1) {
                $cantidadEdadBaja++;
            }
        }
        $observaciones[] = [
            'codigo' => 1,
            'descripcion' => 'EDAD >= 14 años (BAJA)',
            'cantidad' => $cantidadEdadBaja
        ];


        // 3. GES / LAC <= DE 12 AÑOS (GES = abreviatura GES, LAC = abreviatura LAC)
        $cantidadGesLac = 0;
        foreach ($todosBeneficiarios as $ben) {
            $tipo = $ben['beneficiario_tipo'] ?? '';
            if (!empty($tipo) && in_array($tipo, ['GES', 'LAC'])) {
                if ($ben['beneficiario_edad_anos'] <= 12) {
                    $cantidadGesLac++;
                }
            }
        }
        $observaciones[] = [
            'codigo' => 2,
            'descripcion' => 'GES / LAC <= DE 12 AÑOS',
            'cantidad' => $cantidadGesLac
        ];

        // 5. GES. MAS DE 9 MESES / SIN FECHA DE INGRESO (BAJA) (GES = abreviatura GES)
        $cantidadGesBaja = 0;
        foreach ($todosBeneficiarios as $ben) {
            $tipo = $ben['beneficiario_tipo'] ?? '';
            if ($tipo === 'GES') {
                $fechaInicio = $ben['fecha_inicio'] ?? '';
                if (empty($fechaInicio) || (strtotime($fechaInicio) && Carbon::parse($fechaInicio)->diffInMonths(Carbon::now()) > 9)) {
                    if (!empty($ben['beneficiario_baja']) && $ben['beneficiario_baja'] != 1) {
                        $cantidadGesBaja++;
                    }
                }
            }
        }
        $observaciones[] = [
            'codigo' => 3,
            'descripcion' => 'GES. MAS DE 9 MESES / SIN FECHA DE INGRESO (BAJA)',
            'cantidad' => $cantidadGesBaja
        ];

        // 6. LAC. MAS DE UN AÑO / SIN FECHA INGRESO (BAJA) (LAC = abreviatura LAC)
        $cantidadLacBaja = 0;
        foreach ($todosBeneficiarios as $ben) {
            $tipo = $ben['beneficiario_tipo'] ?? '';
            if ($tipo === 'LAC') {
                $fechaInicio = $ben['fecha_inicio'] ?? '';
                if (empty($fechaInicio) || (strtotime($fechaInicio) && Carbon::parse($fechaInicio)->diffInMonths(Carbon::now()) > 12)) {
                    if (!empty($ben['beneficiario_baja']) && $ben['beneficiario_baja'] != 1) {
                        $cantidadLacBaja++;
                    }
                }
            }
        }
$observaciones[] = [
            'codigo' => 4,
            'descripcion' => 'LAC. MAS DE UN AÑO / SIN FECHA INGRESO (BAJA)',
            'cantidad' => $cantidadLacBaja
        ];
           
        $parentescos = Relationship::orderBy('id')->get(['id', 'title'])->toArray();
        $bajas = ReasonDisqualification::orderBy('id')->get(['id', 'title'])->toArray();
        $tipoBeneficios = TypeBenefit::orderBy('id')->get(['id', 'title', 'abbreviation'])->toArray();

        $data = [
            'beneficiarios' => $beneficiarios,
            'resumen' => $resumen,
            'resumen_filas' => $resumenFilas,
            'observaciones' => $observaciones,
            'club_nombre' => strtoupper($association->name),
            'direccion' => $association->address ?? '',
            'ccpp' => $association->placeSector && $association->placeSector->sector ? $association->placeSector->sector->title : '',
            'presidenta' => $presidenta ?? 'SIN ASIGNAR',
            'zona' => $association && $association->placeSector && $association->placeSector->place
            ? ($association->placeSector->place->code ?? '01')
            : '01',
            'comite' => $association->code ?? $association->id,
            'num_mes' => $mes,
            'periodo' => $periodo,
            'semestre' => $mes <= 6 ? $anio . '-I' : $anio . '-II',
            'mes_nombre' => $meses[(int)$mes] ?? '',
            'anio' => $anio,
            'total_beneficiarios' => collect($beneficiarios)->sum('rowspan'),
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s'),
            'productos_pecosa' => $pecosa ? $pecosa->detailPecosas : collect([]),
            'parentescos' => $parentescos,
            'tipo_beneficios' => $tipoBeneficios,
            'bajas' => $bajas,
        ];

        $pdf = \PDF::loadView('reporte_beneficiario', $data);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('padron-beneficiarios-' . $association->code . '-' . $mes . '-' . $anio . '.pdf');
    }
}
