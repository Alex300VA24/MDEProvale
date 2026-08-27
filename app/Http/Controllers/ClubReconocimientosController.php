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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\ConnectionException;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\PDF;
use App\Services\AssociationStateService;
use App\Services\ResolutionStateService;

class ClubReconocimientosController extends Controller
{
    // ==================== ÍNDICE PRINCIPAL ====================

    public function index(Request $request)
    {
        app(AssociationStateService::class)->syncAll();
        $query = Association::with(['state', 'resolution', 'resolutionsHistory', 'partners.people']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        if ($request->has('resolution_id') && $request->resolution_id != '') {
            $query->where('resolution_id', $request->resolution_id);
        }

        $associations = $query->orderBy('id', 'desc')->paginate(10);

        // ── Batch-load president data (elimina N+1 en getPresidentName()) ──
        Association::hydratePresidents($associations);

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

        $states = State::forAssociations()->get();
        $placeSectors = PlaceSector::with(['place', 'sector'])->get();
        $typePremises = TypePremises::all();
        $resolutions = Resolution::orderBy('date_document', 'desc')->get();

        return view('club-reconocimientos.index', compact('associations', 'states', 'placeSectors', 'typePremises', 'resolutions'));
    }

    // ==================== CLUB DE MADRES ====================

    public function storeClub(Request $request)
    {
        // Validación de datos
        $request->validate([
            'resolution_id' => 'required|exists:resolutions,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'company_name' => 'required|string|max:150',
            'address' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'place_sector_id' => 'required|exists:place_sectors,id',
            'type_premises_id' => 'required|exists:type_premises,id',
            'observation' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $estadoVigente = State::where('abbreviation', State::CURRENT)->firstOrFail();

            // Crear el comité vinculado a la resolución existente
            $association = Association::create([
                'name' => $request->name,
                'code' => $request->code,
                'company_name' => $request->company_name,
                'address' => $request->address,
                'place_sector_id' => $request->place_sector_id,
                'type_premises_id' => $request->type_premises_id,
                'resolution_id' => $request->resolution_id,
                'state_id' => $estadoVigente->id,
                'observation' => $request->observation,
            ]);

            app(AssociationStateService::class)->sync($association);

            DB::commit();
            return redirect()->route('club-reconocimientos.index')->with('success', 'Comité registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    public function updateClub(Request $request, Association $association)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'required|string|max:150',
            'code' => 'required|string|max:20',
            'resolution_id' => 'nullable|exists:resolutions,id',
            'place_sector_id' => 'nullable|exists:place_sectors,id',
            'type_premises_id' => 'nullable|exists:type_premises,id',
            'observation' => 'nullable|string',
        ]);

        $association->update($validated);
        app(AssociationStateService::class)->sync($association->fresh('resolution'));
        Association::clearPresidentaCache($association->id);
        return redirect()->route('club-reconocimientos.index')->with('success', 'Club de Madres actualizado exitosamente');
    }

    public function destroyClub(Association $association)
    {
        $hasPartners = $association->partners()->exists();
        $hasPecosas = $association->pecosas()->exists();
        $hasHistory = DB::table('resolution_associations')
            ->where('association_id', $association->id)
            ->exists();

        if ($hasPartners || $hasPecosas || $hasHistory) {
            return redirect()->route('club-reconocimientos.index')
                ->with('error', 'No se puede eliminar el comité porque tiene socios, pecosas o historial de resoluciones asociado.');
        }

        $association->delete();
        return redirect()->route('club-reconocimientos.index')->with('success', 'Club de Madres eliminado exitosamente');
    }

    // ==================== RESOLUCIONES ====================

    public function indexReconocimientos(Request $request)
    {
        app(ResolutionStateService::class)->syncAll();
        $query = Resolution::with(['state', 'associations', 'primaryAssociations']);

        // Filtro de búsqueda por documento
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('document', 'like', "%{$search}%");
        }

        // Filtro por estado
        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        // Filtro por año
        if ($request->has('anio') && $request->anio != '') {
            $query->whereBetween('date_start', [
                $request->anio . '-01-01',
                $request->anio . '-12-31',
            ]);
        }

        $resolutions = $query->orderBy('date_document', 'desc')->paginate(10);
        $states = State::temporal()->get();
        
        return view('club-reconocimientos.reconocimientos.index', compact('resolutions', 'states'));
    }

    public function storeReconocimiento(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);
        
        $resolution = Resolution::create($validated);
        app(ResolutionStateService::class)->sync($resolution);
        
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución creada exitosamente');
    }

    public function updateReconocimiento(Request $request, Resolution $resolution)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);
        
        $resolution->update($validated);
        app(ResolutionStateService::class)->sync($resolution);
        Association::where('resolution_id', $resolution->id)
            ->orWhereHas('resolutionsHistory', fn ($query) => $query->whereKey($resolution->id))
            ->with(['resolution', 'resolutionsHistory'])
            ->get()
            ->each(fn ($association) => app(AssociationStateService::class)->sync($association));
        
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución actualizada exitosamente');
    }

    public function destroyReconocimiento(Resolution $resolution)
    {
        $hasPivot = DB::table('resolution_associations')
            ->where('resolution_id', $resolution->id)
            ->exists();

        $hasAssociations = Association::where('resolution_id', $resolution->id)->exists();
        $hasDirectives = Directive::where('resolution_id', $resolution->id)->exists();

        if ($hasPivot || $hasAssociations || $hasDirectives) {
            return redirect()->route('club-reconocimientos.reconocimientos.index')
                ->with('error', 'No se puede eliminar la resolución porque tiene comités o directivas asociadas.');
        }

        $resolution->delete();
        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución eliminada exitosamente');
    }

    /**
     * Asignar presidenta a un comité.
     * La vigencia de la asociación depende únicamente de su resolución.
     */
    public function asignarPresidenta(Request $request, Association $association)
    {
        $request->validate([
            'partner_id' => 'required|exists:partners,id',
        ]);

        $perteneceAlComite = $association->partners()
            ->whereKey($request->partner_id)
            ->exists();

        if (!$perteneceAlComite) {
            return back()->withInput()->with('error', 'La socia seleccionada no pertenece a este comité.');
        }

        try {
            DB::beginTransaction();

            $posicionPresidenta = Position::firstOrCreate(
                ['title' => 'PRESIDENTA']
            );

            $estadoVigente = State::where('abbreviation', State::CURRENT)->firstOrFail();
            $estadoVencido = State::where('abbreviation', State::EXPIRED)->firstOrFail();

            $resolutionIds = DB::table('resolution_associations')
                ->where('association_id', $association->id)
                ->pluck('resolution_id');
            
            if ($association->resolution_id) {
                $resolutionIds->push($association->resolution_id);
            }
            
            $resolutionIds = $resolutionIds->unique();

            $partnerIds = $association->partners()->pluck('id');

            Directive::whereIn('partner_id', $partnerIds)
                ->whereIn('resolution_id', $resolutionIds)
                ->where('position_id', $posicionPresidenta->id)
                ->update(['state_id' => $estadoVencido->id]);

            $resolutionId = $association->resolution_id 
                ?? $resolutionIds->first() 
                ?? null;

            if (!$resolutionId) {
                throw new \Exception('No hay resolución asociada al comité');
            }

            $directive = Directive::create([
                'resolution_id' => $resolutionId,
                'partner_id'    => $request->partner_id,
                'position_id'   => $posicionPresidenta->id,
                'state_id'      => $estadoVigente->id,
                'date_start'    => now()->toDateString(),
            ]);

            Association::clearPresidentaCache($association->id);

            DB::commit();
            return redirect()->route('club-reconocimientos.index')
                ->with('success', 'Presidenta asignada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al asignar presidenta: ' . $e->getMessage());
        }
    }

    // ==================== RESOLUCIÓN EXTERNA (Portal Municipal) ====================

    private const MUNI_BASE_URL = 'https://www.muniesperanza.gob.pe';
    private const MUNI_SEARCH_URL = self::MUNI_BASE_URL . '/website/loads/cargar_archivos.php';
    private const MUNI_TIPO_RESOLUCION_ALCALDIA = 2;

    /**
     * Busca la resolución en el portal de transparencia de la Municipalidad
     * y confirma si el PDF existe, antes de abrir el modal de vista previa.
     */
    public function buscarResolucionExterna(Resolution $resolution)
    {
        $match = $this->resolveResolucionExterna($resolution);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró esta resolución en el portal de la Municipalidad de La Esperanza.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'titulo' => $match['titulo'],
            'fecha' => $match['fecha'],
            'preview_url' => route('club-reconocimientos.reconocimientos.externa.preview', $resolution),
            'download_url' => route('club-reconocimientos.reconocimientos.externa.descargar', $resolution),
        ]);
    }

    public function previewResolucionExterna(Resolution $resolution)
    {
        return $this->streamResolucionExterna($resolution, 'inline');
    }

    public function descargarResolucionExterna(Resolution $resolution)
    {
        return $this->streamResolucionExterna($resolution, 'attachment');
    }

    private function streamResolucionExterna(Resolution $resolution, string $disposition)
    {
        $match = $this->resolveResolucionExterna($resolution);

        abort_if(!$match, 404, 'No se encontró esta resolución en el portal de la Municipalidad.');

        try {
            $pdf = $this->municipalHttpClient(20)->get($match['pdf_url']);
        } catch (ConnectionException $e) {
            abort(502, 'No se pudo conectar con el portal de la Municipalidad.');
        }

        abort_if(!$pdf->successful(), 502, 'El portal de la Municipalidad no devolvió el PDF esperado.');

        $filename = 'Resolucion-' . $resolution->document . '-MDE.pdf';

        return response($pdf->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }

    /**
     * El buscador del portal municipal solo filtra por año + mes + texto exacto.
     * `date_start` es la fecha real de emisión (viene del mismo reporte oficial
     * usado para poblar la tabla resolutions), así que se usa como proxy del mes.
     */
    private function resolveResolucionExterna(Resolution $resolution): ?array
    {
        if (!$resolution->document || !$resolution->date_start) {
            return null;
        }

        return Cache::remember('resolucion_externa_' . $resolution->id, 3600, function () use ($resolution) {
            [$numero, $anio] = array_pad(explode('-', $resolution->document, 2), 2, null);

            if (!$numero || !$anio) {
                return null;
            }

            $mes = $resolution->date_start->month;

            try {
                $response = $this->municipalHttpClient(15)
                    ->get(self::MUNI_SEARCH_URL, [
                        'd' => "{$anio}|{$mes}|{$numero}|" . self::MUNI_TIPO_RESOLUCION_ALCALDIA,
                    ]);
            } catch (ConnectionException $e) {
                return null;
            }

            if (!$response->successful()) {
                return null;
            }

            if (!preg_match("/window\.open\('([^']+\.pdf)'/i", $response->body(), $pdfMatch)) {
                return null;
            }

            $relativePath = ltrim(str_replace('../../', '', $pdfMatch[1]), '/');

            // La fila tiene dos celdas que empiezan con "RESOLUCION": la categoría
            // ("RESOLUCIONES DE ALCALDÍA") y el título con el número ("...N°0220-2025-MDE").
            // Se exige el patrón número-guion-año para quedarse con el título.
            preg_match('/RESOLUCION[^<]*\d{2,6}-\d{4}[^<]*/i', $response->body(), $tituloMatch);
            preg_match('/(\d{2}\/\d{2}\/\d{4})/', $response->body(), $fechaMatch);

            return [
                'pdf_url' => self::MUNI_BASE_URL . '/' . $relativePath,
                'titulo' => $tituloMatch[0] ?? $resolution->document,
                'fecha' => $fechaMatch[1] ?? null,
            ];
        });
    }

    /**
     * El portal municipal no entrega una cadena de certificados completa.
     * La excepción SSL queda limitada a este host externo conocido.
     */
    private function municipalHttpClient(int $timeout)
    {
        return Http::timeout($timeout)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->withoutVerifying();
    }

    // ==================== REPORTES ====================

    /**
     * Generar Padrón de Club de Madres con Resoluciones de Reconocimiento — HISTÓRICO.
     * Filtra comités, presidenta y beneficiarios vigentes en el mes/año consultado.
     */
    public function generarPadronClub(Request $request)
    {
        $mes  = (int) $request->get('month', date('n'));
        $anio = (int) $request->get('year', date('Y'));

        // Si no se envió mes/año, mostrar formulario de filtros
        if (!$request->filled('year')) {
            return view('club-reconocimientos.padron-filtros', compact('mes', 'anio'));
        }

        $startDate  = \Carbon\Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $endDate    = \Carbon\Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
        $cutoffDate = $endDate->toDateString();

        // Comités que tenían resolución vigente en el periodo consultado
        $associations = Association::with([
            'placeSector.place',
            'placeSector.sector',
            'resolution',
            'resolutionsHistory',
            'state',
            'typePremises',
        ])
        ->whereHas('resolution', function ($q) use ($startDate, $endDate) {
            // La resolución del comité cubría el periodo
            $q->where('date_start', '<=', $endDate->toDateString())
              ->where(function ($q2) use ($startDate) {
                  $q2->whereNull('date_end')
                     ->orWhere('date_end', '>=', $startDate->toDateString());
              });
        })
        ->get();

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
                    'numero'              => $zonaNumero,
                    'nombre'              => $zonaNombre,
                    'sectores'            => [],
                    'lista_sectores'      => [],
                    'clubes'              => [],
                    'total_osb'           => 0,
                    'total_cvl'           => 0,
                    'total_cdm'           => 0,
                    'total_zona'          => 0,
                    'total_beneficiarios' => 0,
                    'totales_rs'          => [],
                ];
            }

            if (!in_array($sector, $zonaGroups[$zonaNumero]['lista_sectores'])) {
                $zonaGroups[$zonaNumero]['lista_sectores'][] = $sector;
            }

            if (!isset($zonaGroups[$zonaNumero]['sectores'][$sector])) {
                $zonaGroups[$zonaNumero]['sectores'][$sector] = [
                    'nombre'       => $sector,
                    'clubes'       => [],
                    'total_sector' => 0,
                ];
            }

            // Presidenta vigente en el periodo (histórica)
            $presidenta = $association->getPresidentNameAt($cutoffDate) ?? '';

            // Beneficiarios vigentes en el periodo:
            // socios vigentes → sus beneficiarios con historial vigente en el periodo
            $totalBenef = \App\Models\Partner::where('association_id', $association->id)
                ->where(function ($q) use ($endDate) {
                    $q->whereNull('date_begin')
                      ->orWhere('date_begin', '<=', $endDate->toDateString());
                })
                ->where(function ($q) use ($startDate) {
                    $q->whereNull('date_end')
                      ->orWhere('date_end', '>=', $startDate->toDateString());
                })
                ->withCount(['beneficiaries as benef_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereHas('histories', function ($h) use ($startDate, $endDate) {
                        $h->where('date_begin', '<=', $endDate->toDateString())
                          ->where(function ($h2) use ($startDate) {
                              $h2->whereNull('date_end')
                                 ->orWhere('date_end', '>=', $startDate->toDateString());
                          });
                    });
                }])
                ->get()
                ->sum('benef_count');

            // Resoluciones del comité — todas las que cubrían el periodo
            $resolutionsAll = [];
            if ($association->resolution) {
                $resolutionsAll[] = $association->resolution;
            }
            foreach ($association->resolutionsHistory as $res) {
                if ($res->id !== $association->resolution_id) {
                    $resolutionsAll[] = $res;
                }
            }

            // Filtrar solo las resoluciones vigentes en o antes del periodo
            $resolutionsAll = array_filter($resolutionsAll, function ($r) use ($endDate) {
                return $r->date_start && \Carbon\Carbon::parse($r->date_start)->lte($endDate);
            });

            usort($resolutionsAll, function ($a, $b) {
                return strtotime($a->date_start) - strtotime($b->date_start);
            });

            $resolutionsAll = array_values($resolutionsAll);
            $totalResolutions = count($resolutionsAll);

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

            $fecha_inicio  = '';
            $fecha_termino = '';
            if (!empty($ultimasResoluciones)) {
                $lastRes = $ultimasResoluciones[count($ultimasResoluciones) - 1];
                $fecha_inicio  = $lastRes->date_start ? $lastRes->date_start->format('d/m/Y') : '';
                $fecha_termino = $lastRes->date_end   ? $lastRes->date_end->format('d/m/Y')   : '';
            }

            $local = $association->typePremises ? $association->typePremises->title : '';

            $club = [
                'numero'       => count($zonaGroups[$zonaNumero]['sectores'][$sector]['clubes']) + 1,
                'codigo'       => $association->code ?? '',
                'razon_social' => $association->company_name ?? '',
                'nombre'       => strtoupper($association->name),
                'direccion'    => $association->address ?? '',
                'sector'       => $sector,
                'beneficiarios'=> $totalBenef,
                'presidenta'   => $presidenta,
                'resolucion_1' => $resolucion_1,
                'resolucion_2' => $resolucion_2,
                'resolucion_3' => $resolucion_3,
                'fecha_inicio' => $fecha_inicio,
                'fecha_termino'=> $fecha_termino,
                'local'        => $local,
            ];

            $zonaGroups[$zonaNumero]['sectores'][$sector]['clubes'][] = $club;
            $zonaGroups[$zonaNumero]['sectores'][$sector]['total_sector']++;
            $zonaGroups[$zonaNumero]['total_cdm']++;
            $zonaGroups[$zonaNumero]['total_zona']++;
            $zonaGroups[$zonaNumero]['total_beneficiarios'] += $totalBenef;

            $rs = strtoupper(trim($association->company_name ?? ''));
            if (!isset($zonaGroups[$zonaNumero]['totales_rs'][$rs])) {
                $zonaGroups[$zonaNumero]['totales_rs'][$rs] = 0;
            }
            $zonaGroups[$zonaNumero]['totales_rs'][$rs]++;
        }

        $totalesRS = ['OSB' => 0, 'CVL' => 0, 'CDM' => 0];
        $totalesGenerales = [
            'total_osb'          => 0,
            'total_cvl'          => 0,
            'total_cdm'          => 0,
            'total_beneficiarios'=> 0,
            'total_acumulado'    => count($associations),
        ];

        foreach ($zonaGroups as $zona) {
            $totalesGenerales['total_cdm'] += $zona['total_cdm'];
            $totalesGenerales['total_beneficiarios'] += $zona['total_beneficiarios'];

            foreach ($zona['totales_rs'] as $rs => $count) {
                $rsKey = strtoupper(trim($rs));
                if ($rsKey === 'OSB') {
                    $totalesGenerales['total_osb'] += $count;
                    $totalesRS['OSB'] += $count;
                } elseif ($rsKey === 'CVL') {
                    $totalesGenerales['total_cvl'] += $count;
                    $totalesRS['CVL'] += $count;
                } elseif ($rsKey === 'CDM') {
                    $totalesRS['CDM'] += $count;
                }
            }
        }

        ksort($zonaGroups);

        $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
                  'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

        $data = [
            'zonas'             => array_values($zonaGroups),
            'totales_generales' => $totalesGenerales,
            'totales_rs'        => $totalesRS,
            'mes_nombre'        => $meses[$mes] ?? '',
            'anio'              => $anio,
            'periodo'           => $meses[$mes] . ' ' . $anio,
            'fecha'             => date('d/m/Y'),
            'hora'              => date('H:i:s'),
        ];

        $pdf = PDF::loadView('padron_club', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('padron-club-madres-' . $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf');
    }
}
