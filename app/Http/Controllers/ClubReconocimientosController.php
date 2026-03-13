<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Resolution;
use App\Models\Directive;
use App\Models\Partner;
use App\Models\Position;
use App\Models\State;
use App\Models\PlaceSector;
use App\Models\TypePremises;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClubReconocimientosController extends Controller
{
    // ==================== ÍNDICE PRINCIPAL ====================

    public function index(Request $request)
    {
        $query = Association::with(['state', 'resolution', 'resolutionsHistory', 'partners.people']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $associations = $query->orderBy('id', 'desc')->paginate(10);

        foreach ($associations as $association) {
            $resolutionsAll = [];
            if ($association->resolution) {
                $resolutionsAll[] = $association->resolution;
            }
            foreach ($association->resolutionsHistory as $res) {
                if ($res->id !== $association->resolution_id) {
                    $resolutionsAll[] = $res;
                }
            }
            
            usort($resolutionsAll, function ($a, $b) {
                return strtotime($b->date_start) - strtotime($a->date_start);
            });

            $association->allResolutions = $resolutionsAll;
            $association->latestResolution = isset($resolutionsAll[0]) ? $resolutionsAll[0] : null;
        }

        return view('club-reconocimientos.index', compact('associations'));
    }

    // ==================== CLUB DE MADRES ====================

    public function createClub(Request $request)
    {
        $states = State::all();
        $placeSectors = PlaceSector::with(['place', 'sector'])->get();
        $typePremises = TypePremises::all();
        $resolutions = Resolution::where('state_id', State::where('abbreviation', 'A')->first()->id ?? 1)
            ->orderBy('date_document', 'desc')
            ->get();
        
        return view('club-reconocimientos.create', compact('states', 'placeSectors', 'typePremises', 'resolutions'));
    }

    public function storeClub(Request $request)
    {
        // Validación de datos
        $request->validate([
            'resolution_id' => 'required|exists:resolutions,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'required|string|max:500',
            'place_sector_id' => 'required|exists:place_sectors,id',
            'type_premises_id' => 'nullable|exists:type_premises,id',
        ]);

        try {
            DB::beginTransaction();

            // Obtener estados
            $estadoInhabilitado = State::where('abbreviation', 'I')->first();
            
            if (!$estadoInhabilitado) {
                $estadoInhabilitado = State::where('abbreviation', 'I')->first();
            }

            // Crear el comité vinculado a la resolución existente
            // El comité se crea INHABILITADO hasta que se asigne una presidenta
            $association = Association::create([
                'name' => $request->name,
                'code' => $request->code,
                'company_name' => $request->company_name,
                'address' => $request->address,
                'place_sector_id' => $request->place_sector_id,
                'type_premises_id' => $request->type_premises_id,
                'resolution_id' => $request->resolution_id,
                'state_id' => $estadoInhabilitado->id,
                'observation' => $request->observation,
            ]);

            DB::commit();
            return redirect()->route('club-reconocimientos.index')->with('success', 'Comité registrado exitosamente. Debe asignar una presidenta para habilitar el comité.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    public function showClub(Association $association)
    {
        $association->load(['partners', 'resolution', 'resolutionsHistory']);
        
        $resolutionsAll = [];
        if ($association->resolution) {
            $resolutionsAll[] = $association->resolution;
        }
        foreach ($association->resolutionsHistory as $res) {
            if ($res->id !== $association->resolution_id) {
                $resolutionsAll[] = $res;
            }
        }
        
        usort($resolutionsAll, function ($a, $b) {
            return strtotime($b->date_start) - strtotime($a->date_start);
        });

        $association->allResolutions = $resolutionsAll;
        
        return view('club-reconocimientos.show', compact('association'));
    }

    public function editClub(Association $association)
    {
        return view('club-reconocimientos.edit', compact('association'));
    }

    public function updateClub(Request $request, Association $association)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:10',
        ]);

        $association->update($validated);
        return redirect()->route('club-reconocimientos.index')->with('success', 'Club de Madres actualizado exitosamente');
    }

    public function destroyClub(Association $association)
    {
        $association->delete();
        return redirect()->route('club-reconocimientos.index')->with('success', 'Club de Madres eliminado exitosamente');
    }

    // ==================== RESOLUCIONES ====================

    public function indexReconocimientos(Request $request)
    {
        $query = Resolution::with(['state', 'associations']);

        // Filtro de búsqueda por documento
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('document', 'like', "%{$search}%");
        }

        // Filtro por estado
        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        // Filtro por vigencia
        if ($request->has('vigencia') && $request->vigencia != '') {
            $today = date('Y-m-d');
            if ($request->vigencia == 'vigentes') {
                $query->where('date_end', '>=', $today);
            } elseif ($request->vigencia == 'vencidas') {
                $query->where('date_end', '<', $today);
            }
        }

        // Filtro por año
        if ($request->has('anio') && $request->anio != '') {
            $query->whereYear('date_start', $request->anio);
        }

        $resolutions = $query->orderBy('date_document', 'desc')->paginate(10);
        $states = State::all();
        
        return view('club-reconocimientos.reconocimientos.index', compact('resolutions', 'states'));
    }

    public function createReconocimiento()
    {
        $states = State::all();
        return view('club-reconocimientos.reconocimientos.create', compact('states'));
    }

    public function storeReconocimiento(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'state_id' => 'required|exists:states,id',
        ]);
        
        Resolution::create($validated);
        
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución creada exitosamente');
    }

    public function showReconocimiento(Resolution $resolution)
    {
        return view('club-reconocimientos.reconocimientos.show', compact('resolution'));
    }

    public function editReconocimiento(Resolution $resolution)
    {
        $states = State::all();
        return view('club-reconocimientos.reconocimientos.edit', compact('resolution', 'states'));
    }

    public function updateReconocimiento(Request $request, Resolution $resolution)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'state_id' => 'required|exists:states,id',
        ]);
        
        $resolution->update($validated);
        
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución actualizada exitosamente');
    }

    public function destroyReconocimiento(Resolution $resolution)
    {
        // Verificar si hay comités asociados
        if ($resolution->associations()->count() > 0) {
            return redirect()->route('club-reconocimientos.reconocimientos.index')
                ->with('error', 'No se puede eliminar la resolución porque tiene comités asociados.');
        }
        
        $resolution->delete();
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución eliminada exitosamente');
    }

    /**
     * Asignar presidenta a un comité.
     * Al asignarse, el comité queda habilitado para operar.
     */
    public function asignarPresidenta(Request $request, Association $association)
    {
        $request->validate([
            'partner_id' => 'required|exists:partners,id',
        ]);

        try {
            DB::beginTransaction();

            // Buscar o crear la posición "PRESIDENTA"
            $posicionPresidenta = Position::firstOrCreate(
                ['title' => 'PRESIDENTA']
            );

            $estadoActivo = State::where('abbreviation', 'A')->first();

            // Desactivar presidentas anteriores del mismo comité
            $resolutionId = $association->resolution_id;
            if ($resolutionId) {
                Directive::where('resolution_id', $resolutionId)
                    ->where('position_id', $posicionPresidenta->id)
                    ->update(['state_id' => State::where('abbreviation', 'I')->first()->id ?? 1]);
            }

            // Obtener los datos de la socia seleccionada
            $partner = Partner::with('people')->find($request->partner_id);
            $nombrePresidenta = '';
            if ($partner && $partner->people) {
                $nombrePresidenta = $partner->people->names . ' ' . $partner->people->father_lastname . ' ' . $partner->people->mother_lastname;
            }

            // Crear nueva directiva de presidenta usando la resolución del comité
            Directive::create([
                'resolution_id' => $resolutionId,
                'partner_id' => $request->partner_id,
                'position_id' => $posicionPresidenta->id,
                'state_id' => $estadoActivo->id,
            ]);

            // Habilitar el comité y guardar el nombre de la presidenta
            $association->update([
                'state_id' => $estadoActivo->id,
                'president' => $nombrePresidenta,
            ]);

            DB::commit();
            return redirect()->route('club-reconocimientos.index')
                ->with('success', 'Presidenta asignada. El comité ahora está habilitado para operar.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al asignar presidenta: ' . $e->getMessage());
        }
    }

    // ==================== REPORTES ====================

    public function reportesClub()
    {
        return view('club-reconocimientos.reportes');
    }

    public function generarReporteClub($tipo, Request $request)
    {
        $query = Association::query();

        switch ($tipo) {
            case 'general':
                $associations = $query->get();
                $titulo = 'Listado General de Club de Madres';
                break;
            case 'socios':
                $associations = $query->with('partners.people')->get();
                $titulo = 'Club de Madres con Socios';
                break;
            case 'estadistico':
                $associations = $query->withCount('partners')->get();
                $titulo = 'Reporte Estadístico de Club de Madres';
                break;
            case 'reconocimientos':
                $associations = $query->with('resolution')->get();
                $titulo = 'Club de Madres con Reconocimientos';
                break;
            default:
                $associations = $query->get();
                $titulo = 'Reporte de Club de Madres';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('club-reconocimientos.reportes.pdf', compact('associations', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-club-de-madres-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }

    public function reportesReconocimientos()
    {
        return view('club-reconocimientos.reconocimientos.reportes');
    }

    public function generarReporteReconocimientos($tipo, Request $request)
    {
        $query = Resolution::with(['state', 'associations']);

        switch ($tipo) {
            case 'general':
                $resolutions = $query->get();
                $titulo = 'Todos los Reconocimientos';
                break;
            case 'club':
                $associationId = $request->get('association_id');
                $resolutions = $query->where('association_id', $associationId)->get();
                $association = Association::find($associationId);
                $titulo = 'Reconocimientos del Club: ' . ($association->name ?? 'N/A');
                break;
            case 'anio':
                $anio = $request->get('anio', date('Y'));
                $resolutions = $query->whereYear('date_start', $anio)->get();
                $titulo = 'Reconocimientos del Año ' . $anio;
                break;
            case 'vigentes':
                $resolutions = $query->where('date_end', '>=', date('Y-m-d'))->get();
                $titulo = 'Reconocimientos Vigentes';
                break;
            case 'estadistico':
                $resolutions = $query->get();
                $titulo = 'Reporte Estadístico de Reconocimientos';
                break;
            default:
                $resolutions = $query->get();
                $titulo = 'Reporte de Reconocimientos';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('club-reconocimientos.reconocimientos.reportes.pdf', compact('resolutions', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-reconocimientos-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Generar Padrón de Club de Madres con Resoluciones de Reconocimiento
     */
    public function generarPadronClub(Request $request)
{
    $associations = Association::with([
        'placeSector.place',
        'placeSector.sector',
        'resolution',
        'resolutionsHistory',
        'partners.beneficiaries',
        'state',
        'typePremises',
    ])->get();

    $zonaGroups = [];
    foreach ($associations as $association) {
        $zonaNombre = $association->placeSector && $association->placeSector->place
            ? $association->placeSector->place->title
            : 'SIN ZONA';
        $zonaNumero = $association->placeSector && $association->placeSector->place
            ? $association->placeSector->place->id
            : 0;

        $sector = $association->placeSector && $association->placeSector->sector
            ? $association->placeSector->sector->title
            : 'SIN SECTOR';

        if (!isset($zonaGroups[$zonaNumero])) {
            $zonaGroups[$zonaNumero] = [
                'numero' => $zonaNumero,
                'nombre' => $zonaNombre,
                'sectores' => [],
                'lista_sectores' => [],
                'clubes' => [],
                'total_osb' => 0,
                'total_cvl' => 0,
                'total_cdm' => 0,
                'total_zona' => 0,
                'total_beneficiarios' => 0,
                'totales_rs' => [],
            ];
        }

        if (!in_array($sector, $zonaGroups[$zonaNumero]['lista_sectores'])) {
            $zonaGroups[$zonaNumero]['lista_sectores'][] = $sector;
        }

        if (!isset($zonaGroups[$zonaNumero]['sectores'][$sector])) {
            $zonaGroups[$zonaNumero]['sectores'][$sector] = [
                'nombre' => $sector,
                'clubes' => [],
                'total_sector' => 0,
            ];
        }

        $presidenta = $association->president ?? '';
        
        if (empty($presidenta)) {
            $directiva = Directive::whereHas('resolution', function ($q) use ($association) {
                $q->where('association_id', $association->id);
            })->whereHas('position', function ($q) {
                $q->where('title', 'like', '%PRESIDENTA%');
            })->whereHas('state', function ($q) {
                $q->where('abbreviation', 'ACTI');
            })->with('partner.people')->first();

            if ($directiva && $directiva->partner && $directiva->partner->people) {
                $p = $directiva->partner->people;
                $presidenta = strtoupper($p->names . ' ' . $p->father_lastname . ' ' . $p->mother_lastname);
            }
        }

        $totalBenef = 0;
        foreach ($association->partners as $partner) {
            $totalBenef += $partner->beneficiaries->count();
        }

        $resolutionsAll = [];
        if ($association->resolution) {
            $resolutionsAll[] = $association->resolution;
        }
        foreach ($association->resolutionsHistory as $res) {
            if ($res->id !== $association->resolution_id) {
                $resolutionsAll[] = $res;
            }
        }

        usort($resolutionsAll, function ($a, $b) {
            return strtotime($a->date_start) - strtotime($b->date_start);
        });

        $totalResolutions = count($resolutionsAll);
        $ultimasResoluciones = [];
        
        if ($totalResolutions == 1) {
            $ultimasResoluciones = [$resolutionsAll[0]];
        } elseif ($totalResolutions == 2) {
            $ultimasResoluciones = [$resolutionsAll[0], $resolutionsAll[1]];
        } else {
            $ultimasResoluciones = array_slice($resolutionsAll, -3);
        }

        $resolucion_1 = isset($ultimasResoluciones[0]) ? $ultimasResoluciones[0]->document : '';
        $resolucion_2 = isset($ultimasResoluciones[1]) ? $ultimasResoluciones[1]->document : '';
        $resolucion_3 = isset($ultimasResoluciones[2]) ? $ultimasResoluciones[2]->document : '';

        $fecha_inicio = '';
        $fecha_termino = '';
        if (isset($ultimasResoluciones[count($ultimasResoluciones) - 1])) {
            $lastRes = $ultimasResoluciones[count($ultimasResoluciones) - 1];
            $fecha_inicio = date('d/m/Y', strtotime($lastRes->date_start));
            $fecha_termino = date('d/m/Y', strtotime($lastRes->date_end));
        }

        $local = $association->typePremises ? $association->typePremises->title : '';

        $club = [
            'numero' => count($zonaGroups[$zonaNumero]['sectores'][$sector]['clubes']) + 1,
            'codigo' => $association->code ?? '',
            'razon_social' => $association->company_name ?? '',
            'nombre' => strtoupper($association->name),
            'direccion' => $association->address ?? '',
            'sector' => $sector,
            'beneficiarios' => $totalBenef,
            'presidenta' => $presidenta,
            'resolucion_1' => $resolucion_1,
            'resolucion_2' => $resolucion_2,
            'resolucion_3' => $resolucion_3,
            'fecha_inicio' => $fecha_inicio,
            'fecha_termino' => $fecha_termino,
            'local' => $local,
        ];

        $zonaGroups[$zonaNumero]['sectores'][$sector]['clubes'][] = $club;
        $zonaGroups[$zonaNumero]['sectores'][$sector]['total_sector']++;
        $zonaGroups[$zonaNumero]['total_cdm']++;
        $zonaGroups[$zonaNumero]['total_zona']++;
        $zonaGroups[$zonaNumero]['total_beneficiarios'] = ($zonaGroups[$zonaNumero]['total_beneficiarios'] ?? 0) + $totalBenef;
        
        $rs = strtoupper(trim($association->company_name ?? ''));
        if (!isset($zonaGroups[$zonaNumero]['totales_rs'][$rs])) {
            $zonaGroups[$zonaNumero]['totales_rs'][$rs] = 0;
        }
        $zonaGroups[$zonaNumero]['totales_rs'][$rs]++;
    }

    // Totales generales por R.S.
    $totalesRS = [
        'OSB' => 0,
        'CVL' => 0,
        'CDM' => 0,
    ];
    
    // Totales generales
    $totalesGenerales = [
        'total_osb' => 0,
        'total_cvl' => 0,
        'total_cdm' => 0,
        'total_beneficiarios' => 0,
        'total_acumulado' => count($associations),
    ];
    
    foreach ($zonaGroups as $zona) {
        $totalesGenerales['total_cdm'] += $zona['total_cdm'];
        $totalesGenerales['total_beneficiarios'] += $zona['total_beneficiarios'] ?? 0;
        
        if (isset($zona['totales_rs'])) {
            foreach ($zona['totales_rs'] as $rs => $count) {
                $rsKey = strtoupper(trim($rs));
                if ($rsKey === 'OSB') {
                    $totalesGenerales['total_osb'] += $count;
                    $totalesRS['OSB'] += $count;
                } elseif ($rsKey === 'CVL') {
                    $totalesGenerales['total_cvl'] += $count;
                    $totalesRS['CVL'] += $count;
                } elseif ($rsKey === 'CDM') {
                    $totalesGenerales['total_cdm'] += $count;
                    $totalesRS['CDM'] += $count;
                }
            }
        }
    }

    ksort($zonaGroups);

    $data = [
        'zonas' => array_values($zonaGroups),
        'totales_generales' => $totalesGenerales,
        'totales_rs' => $totalesRS,
        'fecha' => date('d/m/Y'),
        'hora' => date('H:i:s'),
    ];

    $pdf = \PDF::loadView('padron_club', $data)->setPaper('a4', 'landscape');
    
    $dompdf = $pdf->getDomPDF();
    $canvas = $dompdf->get_canvas();
    
    
    return $pdf->stream('padron-club-madres-' . date('Y-m-d') . '.pdf');
}
}
