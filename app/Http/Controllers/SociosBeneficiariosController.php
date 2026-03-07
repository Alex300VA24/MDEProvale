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

        $partners = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        $states = State::all();

        return view('socios-beneficiarios.index', compact('partners', 'associations', 'states'));
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
        return view('socios-beneficiarios.personas.index', compact('people'));
    }

    public function createPersona()
    {
        $placeSectors = PlaceSector::with(['place', 'sector'])->get();
        return view('socios-beneficiarios.personas.create', compact('placeSectors'));
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

    public function showPersona(People $person)
    {
        $person->load('placeSector.place', 'placeSector.sector');
        return view('socios-beneficiarios.personas.show', compact('person'));
    }

    public function editPersona(People $person)
    {
        $placeSectors = PlaceSector::with(['place', 'sector'])->get();
        return view('socios-beneficiarios.personas.edit', compact('person', 'placeSectors'));
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

    public function indexSocios(Request $request)
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

        $partners = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        $states = State::all();

        return view('socios-beneficiarios.socios.index', compact('partners', 'associations', 'states'));
    }

    public function createSocio()
    {
        $people = People::all();
        $associations = Association::all();
        $states = State::all();
        $relationships = Relationship::all();
        return view('socios-beneficiarios.socios.create', compact('people', 'associations', 'states', 'relationships'));
    }

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
        ]);

        $partner = Partner::create($validated);

        if ($request->has('beneficiaries') && !empty($request->beneficiaries)) {
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

        return redirect()->route('socios-beneficiarios.socios.index')->with('success', 'Socio y beneficiarios creados exitosamente');
    }

    public function storePersonAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'names' => 'required|string|max:255',
                'father_lastname' => 'required|string|max:255',
                'mother_lastname' => 'required|string|max:255',
                'dni' => 'required|string|size:8|unique:people,dni',
                'birthdate' => 'required|date',
                'gender' => 'required|in:M,F',
                'address' => 'required|string|max:500',
                'place_sector_id' => 'required|exists:place_sectors,id',
            ]);

            $person = People::create($validated);

            return response()->json([
                'success' => true,
                'person' => $person,
                'message' => 'Persona registrada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar persona: ' . $e->getMessage()
            ], 422);
        }
    }

    public function showSocio(Partner $partner)
    {
        $partner->load(['people', 'association', 'state', 'beneficiaries', 'directives']);
        return view('socios-beneficiarios.socios.show', compact('partner'));
    }

    public function editSocio(Partner $partner)
    {
        $people = People::all();
        $associations = Association::all();
        $states = State::all();
        $relationships = Relationship::all();
        $partner->load('beneficiaries');
        return view('socios-beneficiarios.socios.edit', compact('partner', 'people', 'associations', 'states', 'relationships'));
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

        return view('socios-beneficiarios.beneficiarios.index', compact('beneficiaries', 'partners', 'relationships'));
    }

    public function createBeneficiario()
    {
        $partners = Partner::with('people')->get();
        $relationships = Relationship::all();
        return view('socios-beneficiarios.beneficiarios.create', compact('partners', 'relationships'));
    }

    public function storeBeneficiario(Request $request)
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ]);

        Beneficiarie::create($validated);
        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario creado exitosamente');
    }

    public function showBeneficiario(Beneficiarie $beneficiarie)
    {
        $beneficiarie->load(['person', 'partner', 'relationship']);
        return view('socios-beneficiarios.beneficiarios.show', compact('beneficiarie'));
    }

    public function editBeneficiario(Beneficiarie $beneficiarie)
    {
        $partners = Partner::with('people')->get();
        $relationships = Relationship::all();
        return view('socios-beneficiarios.beneficiarios.edit', compact('beneficiarie', 'partners', 'relationships'));
    }

    public function updateBeneficiario(Request $request, Beneficiarie $beneficiarie)
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ]);

        $beneficiarie->update($validated);
        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario actualizado exitosamente');
    }

    public function destroyBeneficiario(Beneficiarie $beneficiarie)
    {
        $beneficiarie->delete();
        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario eliminado exitosamente');
    }

    // ==================== REPORTES ====================

    public function reportesSocios()
    {
        return view('socios-beneficiarios.socios.reportes');
    }

    public function generarReporteSocios($tipo, Request $request)
    {
        $query = Partner::with(['people', 'association', 'state']);

        switch ($tipo) {
            case 'general':
                $partners = $query->get();
                $titulo = 'Listado General de Socios';
                break;
            case 'club':
                $associationId = $request->get('association_id');
                $partners = $query->where('association_id', $associationId)->get();
                $association = Association::find($associationId);
                $titulo = 'Socios del Club: ' . ($association->name ?? 'N/A');
                break;
            case 'estado':
                $stateId = $request->get('state_id');
                $partners = $query->where('state_id', $stateId)->get();
                $state = State::find($stateId);
                $titulo = 'Socios - Estado: ' . ($state->title ?? 'N/A');
                break;
            case 'fecha':
                $fechaInicio = $request->get('fecha_inicio');
                $fechaFin = $request->get('fecha_fin');
                $partners = $query->whereBetween('date_begin', [$fechaInicio, $fechaFin])->get();
                $titulo = 'Socios del ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin));
                break;
            case 'estadistico':
                $partners = $query->get();
                $titulo = 'Reporte Estadístico de Socios';
                break;
            case 'beneficiarios':
                $partners = $query->with('beneficiaries')->get();
                $titulo = 'Socios con Beneficiarios';
                break;
            default:
                $partners = $query->get();
                $titulo = 'Reporte de Socios';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('socios-beneficiarios.socios.reportes.pdf', compact('partners', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-socios-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }

    public function reportesBeneficiarios()
    {
        return view('socios-beneficiarios.beneficiarios.reportes');
    }

    public function generarReporteBeneficiarios($tipo, Request $request)
    {
        $query = Beneficiarie::with(['person', 'partner.people', 'relationship']);

        switch ($tipo) {
            case 'general':
                $beneficiaries = $query->get();
                $titulo = 'Listado General de Beneficiarios';
                break;
            case 'socio':
                $partnerId = $request->get('partner_id');
                $beneficiaries = $query->where('partner_id', $partnerId)->get();
                $partner = Partner::with('people')->find($partnerId);
                $titulo = 'Beneficiarios del Socio: ' . ($partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname : 'N/A');
                break;
            case 'relacion':
                $relationshipId = $request->get('relationship_id');
                $beneficiaries = $query->where('relationship_id', $relationshipId)->get();
                $relationship = Relationship::find($relationshipId);
                $titulo = 'Beneficiarios - Relación: ' . ($relationship->title ?? 'N/A');
                break;
            case 'estadistico':
                $beneficiaries = $query->get();
                $titulo = 'Reporte Estadístico de Beneficiarios';
                break;
            default:
                $beneficiaries = $query->get();
                $titulo = 'Reporte de Beneficiarios';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('socios-beneficiarios.beneficiarios.reportes.pdf', compact('beneficiaries', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-beneficiarios-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }

    public function imprimirFichaBeneficiario()
    {
        $logoPath = public_path('img/muni2.png');
        $pdf = \PDF::loadView('ficha_beneficiario', compact('logoPath'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('ficha-beneficiario-' . date('Y-m-d-His') . '.pdf');
    }

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
            // Si no se selecciona comité, mostrar formulario de filtros
            return view('socios-beneficiarios.beneficiarios.padron-filtros', compact('associations', 'mes', 'anio'));
        }

        $association = Association::with(['placeSector.place', 'placeSector.sector'])->findOrFail($associationId);

        // Obtener la presidenta actual del comité
        $presidenta = null;
        $directivaPresidenta = Directive::whereHas('resolution', function ($q) use ($associationId) {
            $q->where('association_id', $associationId);
        })->whereHas('position', function ($q) {
            $q->where('title', 'like', '%PRESIDENTA%');
        })->whereHas('state', function ($q) {
            $q->where('abbreviation', 'ACTI');
        })->with('partner.people')->first();

        if ($directivaPresidenta && $directivaPresidenta->partner && $directivaPresidenta->partner->people) {
            $p = $directivaPresidenta->partner->people;
            $presidenta = strtoupper($p->names . ' ' . $p->father_lastname . ' ' . $p->mother_lastname);
        }

        // Obtener socios del comité con sus beneficiarios
        $partners = Partner::with(['people', 'beneficiaries.person', 'beneficiaries.relationship'])
            ->where('association_id', $associationId)
            ->get();

        // Obtener PECOSA del periodo (mes/año) para este comité
        $pecosa = Pecosa::with('detailPecosas.product')
            ->where('association_id', $associationId)
            ->whereMonth('delivery_date', $mes)
            ->whereYear('delivery_date', $anio)
            ->first();

        // Construir array de beneficiarios para la vista
        $beneficiarios = [];
        $resumen = [
            'total' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'madres_otros', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'masculino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'madres_otros', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'femenino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'madres_otros', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
        ];

        foreach ($partners as $partner) {
            $socia = $partner->people;
            if (!$socia) continue;

            $sociaEdad = $socia->birthdate ? Carbon::parse($socia->birthdate)->age : 0;
            $sociaEdadMeses = $socia->birthdate ? Carbon::parse($socia->birthdate)->diff(Carbon::now())->m : 0;

            foreach ($partner->beneficiaries as $beneficiario) {
                $persona = $beneficiario->person;
                if (!$persona) continue;

                $edadAnos = $persona->birthdate ? Carbon::parse($persona->birthdate)->age : 0;
                $edadMeses = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff(Carbon::now())->m : 0;
                $edadDias = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff(Carbon::now())->d : 0;

                $beneficiarios[] = [
                    'fecha_ingreso' => $partner->date_begin ? date('d/m/Y', strtotime($partner->date_begin)) : '',
                    'socia_nombre' => strtoupper($socia->father_lastname . ' ' . $socia->mother_lastname . ' ' . $socia->names),
                    'socia_direccion' => $socia->address ?? '',
                    'socia_dni' => $socia->dni ?? '',
                    'beneficiario_nombre' => strtoupper($persona->father_lastname . ' ' . $persona->mother_lastname . ' ' . $persona->names),
                    'beneficiario_dni' => $persona->dni ?? '',
                    'beneficiario_baja' => '',
                    'beneficiario_edad_anos' => $edadAnos,
                    'beneficiario_edad_meses' => $edadMeses,
                    'beneficiario_amd' => $edadAnos . '-' . $edadMeses . '-' . $edadDias,
                    'beneficiario_ano_ingreso' => $partner->date_begin ? date('Y', strtotime($partner->date_begin)) : '',
                    'beneficiario_fecha_nacimiento' => $persona->birthdate ? date('d/m/Y', strtotime($persona->birthdate)) : '',
                    'socia_baja' => '',
                    'socia_edad_anos' => $sociaEdad,
                    'socia_edad_meses' => $sociaEdadMeses,
                    'socia_amd' => '',
                    'socia_ano_ingreso' => $partner->date_begin ? date('Y', strtotime($partner->date_begin)) : '',
                    'socia_fecha_nacimiento' => $socia->birthdate ? date('d/m/Y', strtotime($socia->birthdate)) : '',
                    'socia_fecha_termino' => $partner->date_end ? date('d/m/Y', strtotime($partner->date_end)) : '',
                    'observaciones' => $partner->observations ?? '',
                ];

                // Resumen por prioridad (niños 0-6 años = 1ra prioridad)
                if ($edadAnos <= 6) {
                    $key = $edadAnos == 1 ? '1_ano' : $edadAnos . '_anos';
                    $resumen['total'][$key]++;
                    $resumen['total']['total']++;
                    $resumen['total']['total_general']++;

                    if ($persona->gender === 'M') {
                        $resumen['masculino'][$key]++;
                        $resumen['masculino']['total']++;
                        $resumen['masculino']['total_general']++;
                    } else {
                        $resumen['femenino'][$key]++;
                        $resumen['femenino']['total']++;
                        $resumen['femenino']['total_general']++;
                    }
                }
            }
        }

        $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $periodo = $anio . '-' . ($mes <= 6 ? 'I' : 'II');

        $data = [
            'beneficiarios' => $beneficiarios,
            'resumen' => $resumen,
            'club_nombre' => strtoupper($association->name),
            'direccion' => $association->address ?? '',
            'ccpp' => $association->placeSector && $association->placeSector->place ? $association->placeSector->place->title : '',
            'presidenta' => $presidenta ?? 'SIN ASIGNAR',
            'zona' => $association->placeSector && $association->placeSector->place ? $association->placeSector->place->title : '01',
            'comite' => $association->code ?? $association->id,
            'num_mes' => $mes,
            'periodo' => $periodo,
            'mes_nombre' => $meses[(int)$mes] ?? '',
            'anio' => $anio,
            'productos_pecosa' => $pecosa ? $pecosa->detailPecosas : collect([]),
        ];

        $pdf = \PDF::loadView('reporte_beneficiario', $data);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('padron-beneficiarios-' . $association->code . '-' . $mes . '-' . $anio . '.pdf');
    }
}
