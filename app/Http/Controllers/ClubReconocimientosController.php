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
        $query = Association::with(['state', 'resolutions']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $associations = $query->orderBy('id', 'desc')->paginate(10);

        return view('club-reconocimientos.index', compact('associations'));
    }

    // ==================== CLUB DE MADRES ====================

    public function indexClub(Request $request)
    {
        return $this->index($request);
    }

    public function createClub(Request $request)
    {
        $states = State::all();
        $placeSectors = PlaceSector::with(['place', 'sector'])->get();
        $typePremises = TypePremises::all();
        
        return view('club-reconocimientos.club.create', compact('states', 'placeSectors', 'typePremises'));
    }

    public function storeClub(Request $request)
    {
        // Validación de datos de la resolución
        $request->validate([
            'resolution_document' => 'required|string|max:255',
            'resolution_date_document' => 'required|date',
            'resolution_date_start' => 'required|date',
            'resolution_date_end' => 'required|date|after:resolution_date_start',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'required|string|max:500',
            'place_sector_id' => 'required|exists:place_sectors,id',
            'type_premises_id' => 'nullable|exists:type_premises,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Primero crear la resolución
            $estadoActivo = State::where('abbreviation', 'ACTI')->first();
            $estadoInhabilitado = State::where('abbreviation', 'INAC')->first();
            
            if (!$estadoActivo) {
                $estadoActivo = State::first();
            }
            if (!$estadoInhabilitado) {
                $estadoInhabilitado = State::first();
            }

            $resolution = Resolution::create([
                'document' => $request->resolution_document,
                'date_document' => $request->resolution_date_document,
                'date_start' => $request->resolution_date_start,
                'date_end' => $request->resolution_date_end,
                'state_id' => $estadoActivo->id,
            ]);

            // 2. Luego crear el comité vinculado a la resolución
            // El comité se crea INHABILITADO hasta que se asigne una presidenta
            $association = Association::create([
                'name' => $request->name,
                'code' => $request->code,
                'address' => $request->address,
                'place_sector_id' => $request->place_sector_id,
                'type_premises_id' => $request->type_premises_id,
                'resolution_id' => $resolution->id,
                'state_id' => $estadoInhabilitado->id,
                'property_number' => $request->property_number,
                'observation' => $request->observation,
            ]);

            DB::commit();
            return redirect()->route('club-reconocimientos.club.index')->with('success', 'Comité y Resolución registrados. Debe asignar una presidenta para habilitar el comité.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    public function showClub(Association $association)
    {
        $association->load(['partners', 'resolution']);
        return view('club-reconocimientos.club.show', compact('association'));
    }

    public function editClub(Association $association)
    {
        return view('club-reconocimientos.club.edit', compact('association'));
    }

    public function updateClub(Request $request, Association $association)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ]);

        $association->update($validated);
        return redirect()->route('club-reconocimientos.club.index')->with('success', 'Club de Madres actualizado exitosamente');
    }

    public function destroyClub(Association $association)
    {
        $association->delete();
        return redirect()->route('club-reconocimientos.club.index')->with('success', 'Club de Madres eliminado exitosamente');
    }

    // ==================== RESOLUCIONES ====================

    public function indexReconocimientos(Request $request)
    {
        $query = Resolution::with(['state']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('document', 'like', "%{$search}%");
        }

        if ($request->has('association_id') && $request->association_id != '') {
            $query->where('association_id', $request->association_id);
        }

        $resolutions = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        return view('club-reconocimientos.reconocimientos.index', compact('resolutions', 'associations'));
    }

    public function createReconocimiento()
    {
        $associations = Association::all();
        $states = State::all();
        return view('club-reconocimientos.reconocimientos.create', compact('associations', 'states'));
    }

    public function storeReconocimiento(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'association_id' => 'required|exists:associations,id',
            'state_id' => 'required|exists:states,id',
        ]);
        
        $resolution = Resolution::create([
            'document' => $validated['document'],
            'date_document' => $validated['date_document'],
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'state_id' => $validated['state_id'],
        ]);
        
        // Asociar la resolución al comité
        $association = Association::find($validated['association_id']);
        if ($association) {
            $association->update(['resolution_id' => $resolution->id]);
        }
        
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución creada exitosamente');
    }

    public function showReconocimiento(Resolution $resolution)
    {
        return view('club-reconocimientos.reconocimientos.show', compact('resolution'));
    }

    public function editReconocimiento(Resolution $resolution)
    {
        $associations = Association::all();
        $states = State::all();
        return view('club-reconocimientos.reconocimientos.edit', compact('resolution', 'associations', 'states'));
    }

    public function updateReconocimiento(Request $request, Resolution $resolution)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'association_id' => 'required|exists:associations,id',
            'state_id' => 'required|exists:states,id',
        ]);
        
        $resolution->update([
            'document' => $validated['document'],
            'date_document' => $validated['date_document'],
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'state_id' => $validated['state_id'],
        ]);
        
        // Actualizar la asociación con el comité
        $oldAssociation = $resolution->associations()->first();
        if ($oldAssociation && $oldAssociation->id != $validated['association_id']) {
            $oldAssociation->update(['resolution_id' => null]);
        }
        
        $newAssociation = Association::find($validated['association_id']);
        if ($newAssociation) {
            $newAssociation->update(['resolution_id' => $resolution->id]);
        }
        
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución actualizada exitosamente');
    }

    public function destroyReconocimiento(Resolution $resolution)
    {
        // Desvincular comités antes de eliminar
        foreach ($resolution->associations as $assoc) {
            $assoc->update(['resolution_id' => null]);
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
            'resolution_id' => 'required|exists:resolutions,id',
        ]);

        try {
            DB::beginTransaction();

            // Buscar o crear la posición "PRESIDENTA"
            $posicionPresidenta = Position::firstOrCreate(
                ['title' => 'PRESIDENTA']
            );

            $estadoActivo = State::where('abbreviation', 'ACTI')->first();

            // Desactivar presidentas anteriores del mismo comité (ahora solo hay una resolución)
            $resolutionId = $association->resolution_id;
            if ($resolutionId) {
                Directive::where('resolution_id', $resolutionId)
                    ->where('position_id', $posicionPresidenta->id)
                    ->update(['state_id' => State::where('abbreviation', 'INAC')->first()->id ?? 1]);
            }

            // Crear nueva directiva de presidenta
            Directive::create([
                'resolution_id' => $request->resolution_id,
                'partner_id' => $request->partner_id,
                'position_id' => $posicionPresidenta->id,
                'state_id' => $estadoActivo->id,
            ]);

            // Habilitar el comité
            $association->update(['state_id' => $estadoActivo->id]);

            DB::commit();
            return redirect()->route('club-reconocimientos.club.show', $association)
                ->with('success', 'Presidenta asignada. El comité ahora está habilitado para operar.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al asignar presidenta: ' . $e->getMessage());
        }
    }

    // ==================== REPORTES ====================

    public function reportesClub()
    {
        return view('club-reconocimientos.club.reportes');
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
                $associations = $query->with('resolutions')->get();
                $titulo = 'Club de Madres con Reconocimientos';
                break;
            default:
                $associations = $query->get();
                $titulo = 'Reporte de Club de Madres';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('club-reconocimientos.club.reportes.pdf', compact('associations', 'titulo', 'tipo'));
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
            'resolutions',
            'partners.beneficiaries',
            'state',
        ])->get();

        // Agrupar por zona (place)
        $zonaGroups = [];
        foreach ($associations as $association) {
            $zonaNombre = $association->placeSector && $association->placeSector->place
                ? $association->placeSector->place->title
                : 'SIN ZONA';
            $zonaNumero = $association->placeSector && $association->placeSector->place
                ? $association->placeSector->place->id
                : 0;

            if (!isset($zonaGroups[$zonaNumero])) {
                $zonaGroups[$zonaNumero] = [
                    'numero' => $zonaNumero,
                    'nombre' => $zonaNombre,
                    'clubes' => [],
                    'total_osb' => 0,
                    'total_cvl' => 0,
                    'total_cdm' => 0,
                    'total_zona' => 0,
                ];
            }

            // Obtener presidenta
            $presidenta = '';
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

            // Contar beneficiarios
            $totalBenef = 0;
            foreach ($association->partners as $partner) {
                $totalBenef += $partner->beneficiaries->count();
            }

            // Resolución (ahora es una sola)
            $resolution = $association->resolution;

            $sector = $association->placeSector && $association->placeSector->sector
                ? $association->placeSector->sector->title
                : '';

            $club = [
                'numero' => count($zonaGroups[$zonaNumero]['clubes']) + 1,
                'codigo' => $association->code ?? '',
                'tipo' => 'CDM',
                'nombre' => strtoupper($association->name),
                'direccion' => $association->address ?? '',
                'sector' => $sector,
                'beneficiarios' => $totalBenef,
                'presidenta' => $presidenta,
                'resolucion_1' => $resolution ? $resolution->document : '',
                'resolucion_2' => '',
                'resolucion_3' => '',
                'fecha_inicio' => $resolution ? date('d/m/Y', strtotime($resolution->date_start)) : '',
                'fecha_termino' => $resolution ? date('d/m/Y', strtotime($resolution->date_end)) : '',
                'local' => $association->property_number ?? '',
            ];

            $zonaGroups[$zonaNumero]['clubes'][] = $club;
            $zonaGroups[$zonaNumero]['total_cdm']++;
            $zonaGroups[$zonaNumero]['total_zona']++;
        }

        // Totales generales
        $totalesGenerales = [
            'total_osb' => 0,
            'total_cvl' => 0,
            'total_cdm' => 0,
            'total_acumulado' => count($associations),
        ];
        foreach ($zonaGroups as $zona) {
            $totalesGenerales['total_cdm'] += $zona['total_cdm'];
        }

        $data = [
            'zonas' => array_values($zonaGroups),
            'totales_generales' => $totalesGenerales,
            'pagina' => 1,
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s'),
        ];

        $pdf = \PDF::loadView('padron_club', $data);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('padron-club-madres-' . date('Y-m-d') . '.pdf');
    }
}
