<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsignarPresidentaRequest;
use App\Http\Requests\StoreClubRequest;
use App\Http\Requests\StoreReconocimientoRequest;
use App\Http\Requests\UpdateClubRequest;
use App\Http\Requests\UpdateReconocimientoRequest;
use App\Http\Resources\ClubResource;
use App\Http\Resources\ReconocimientoResource;
use App\Models\Association;
use App\Models\Directive;
use App\Models\Partner;
use App\Models\Pecosa;
use App\Models\PlaceSector;
use App\Models\Position;
use App\Models\Resolution;
use App\Models\State;
use App\Models\TypePremises;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\AssociationStateService;
use App\Services\PresidentAccountService;
use App\Services\ResolutionStateService;
use App\Services\MunicipalResolutionService;

class ComitesController extends Controller
{
    private const CLUB_WITH = [
        'state:id,title,abbreviation',
        'resolution:id,document,date_document,date_start,date_end,state_id',
        'resolutionsHistory:id,document,date_document,date_start,date_end,state_id',
        'placeSector:id,place_id,sector_id',
        'placeSector.place:id,title',
        'placeSector.sector:id,title',
        'typePremises:id,title',
    ];

    // ==================== COMITÉS (CLUB DE MADRES) ====================

    public function clubs(Request $request)
    {
        app(AssociationStateService::class)->syncAll();
        $query = Association::with(self::CLUB_WITH);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('place_id')) {
            $query->whereHas('placeSector', fn ($q) => $q->where('place_id', $request->place_id));
        }

        if ($request->filled('sector_id')) {
            $query->whereHas('placeSector', fn ($q) => $q->where('sector_id', $request->sector_id));
        }

        if ($request->filled('place_sector_id')) {
            $query->where('place_sector_id', $request->place_sector_id);
        }

        $associations = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 10));

        $this->hydratePresidentData($associations);
        $this->hydrateResolutions($associations);

        return ClubResource::collection($associations);
    }

    public function clubsOptions()
    {
        return response()->json([
            'states' => State::forAssociations()->get(['id', 'title', 'abbreviation']),
            'place_sectors' => PlaceSector::with(['place:id,title', 'sector:id,title'])->get(),
            'type_premises' => TypePremises::select(['id', 'title'])->get(),
            'resolutions' => Resolution::select(['id', 'document', 'date_document', 'date_start', 'date_end', 'state_id'])
                ->orderByDesc('date_document')
                ->get(),
        ]);
    }

    public function club(Association $association)
    {
        app(AssociationStateService::class)->sync($association);
        $association->load(self::CLUB_WITH);
        $association->load(['partners.people:id,names,father_lastname,mother_lastname,dni']);

        $this->setPresidentForSingle($association);
        $this->hydrateResolutions(collect([$association]));

        return new ClubResource($association);
    }

    public function storeClub(StoreClubRequest $request)
    {
        $data = $request->validated();
        $data['state_id'] = State::idFor(State::CURRENT);

        $association = Association::create($data);
        app(AssociationStateService::class)->sync($association);
        $association->load(self::CLUB_WITH);

        $this->setPresidentForSingle($association);
        $this->hydrateResolutions(collect([$association]));

        return (new ClubResource($association))->response()->setStatusCode(201);
    }

    public function updateClub(UpdateClubRequest $request, Association $association)
    {
        $association->update($request->validated());
        app(AssociationStateService::class)->sync($association->fresh('resolution'));
        Association::clearPresidentaCache($association->id);

        $association->load(self::CLUB_WITH);

        $this->setPresidentForSingle($association);
        $this->hydrateResolutions(collect([$association]));

        return new ClubResource($association);
    }

    public function destroyClub(Association $association)
    {
        $hasPartners = $association->partners()->exists();
        $hasPecosas = $association->pecosas()->exists();
        $hasHistory = DB::table('resolution_associations')
            ->where('association_id', $association->id)
            ->exists();

        if ($hasPartners || $hasPecosas || $hasHistory) {
            return response()->json([
                'message' => 'No se puede eliminar: el comité tiene socios, pecosas o historial de resoluciones asociado',
            ], 422);
        }

        $association->delete();

        return response()->json(null, 204);
    }

    /**
     * Asigna la presidenta al comité. Solo se acepta una socia que pertenezca
     * al comité. La vigencia depende únicamente de su resolución.
     */
    public function asignarPresidenta(AsignarPresidentaRequest $request, Association $association)
    {
        $partnerId = (int) $request->partner_id;

        $belongsToClub = $association->partners()
            ->whereKey($partnerId)
            ->exists();

        if (!$belongsToClub) {
            return response()->json([
                'message' => 'La socia seleccionada no pertenece a este comité',
            ], 422);
        }

        try {
            DB::transaction(function () use ($association, $partnerId) {
                $posicionPresidenta = Position::firstOrCreate(['title' => 'PRESIDENTA']);

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

                $resolutionId = $association->resolution_id ?? $resolutionIds->first() ?? null;

                if (!$resolutionId) {
                    throw new \DomainException('No hay resolución asociada al comité');
                }

Directive::create([
                    'resolution_id' => $resolutionId,
                    'partner_id'    => $partnerId,
                    'position_id'   => $posicionPresidenta->id,
                    'state_id'      => $estadoVigente->id,
                    'date_start'    => now()->toDateString(),
                ]);

                $partner = Partner::with('people')->find($partnerId);
                if ($partner) {
                    app(PresidentAccountService::class)->sync($partner);
                }

                Association::clearPresidentaCache($association->id);
            });
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al asignar presidenta: ' . $e->getMessage()], 422);
        }

        $association->load(self::CLUB_WITH);
        $association->president_partner_id = $partnerId;

        $partner = Partner::with('people:id,names,father_lastname')->find($partnerId);
        $association->president_name = $partner && $partner->people
            ? trim($partner->people->names . ' ' . $partner->people->father_lastname)
            : null;

        $this->hydrateResolutions(collect([$association]));

        return new ClubResource($association);
    }

    // ==================== RECONOCIMIENTOS (RESOLUCIONES) ====================

    public function reconocimientos(Request $request)
    {
        app(ResolutionStateService::class)->syncAll();
        $query = Resolution::with([
            'state:id,title,abbreviation',
            'associations:id,code,name',
            'primaryAssociations:id,code,name,resolution_id',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $matchCommittee = function ($associationQuery) use ($search) {
                $associationQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            };

            $query->where(function ($resolutionQuery) use ($search, $matchCommittee) {
                $resolutionQuery
                    ->where('document', 'like', "%{$search}%")
                    ->orWhereHas('associations', $matchCommittee)
                    ->orWhereHas('primaryAssociations', $matchCommittee);
            });
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('anio')) {
            $query->whereBetween('date_start', [
                $request->anio . '-01-01',
                $request->anio . '-12-31',
            ]);
        }

        if ($request->filled('mes')) {
            $month = (int) $request->mes;

            if ($month >= 1 && $month <= 12) {
                $query->whereMonth('date_start', $month);
            }
        }

        $resolutions = $query
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 10));

        return ReconocimientoResource::collection($resolutions);
    }

    public function reconocimientosOptions()
    {
        return response()->json([
            'states' => State::temporal()->get(['id', 'title', 'abbreviation']),
            'years' => Resolution::selectRaw('YEAR(date_start) as year')
                ->whereNotNull('date_start')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year'),
        ]);
    }

    public function storeReconocimiento(StoreReconocimientoRequest $request)
    {
        $resolution = Resolution::create($request->validated());
        app(ResolutionStateService::class)->sync($resolution);

        return (new ReconocimientoResource($resolution->load([
            'state:id,title,abbreviation',
            'associations:id,code,name',
            'primaryAssociations:id,code,name,resolution_id',
        ])))
            ->response()
            ->setStatusCode(201);
    }

    public function updateReconocimiento(UpdateReconocimientoRequest $request, Resolution $resolution)
    {
        $resolution->update($request->validated());
        app(ResolutionStateService::class)->sync($resolution);
        Association::where('resolution_id', $resolution->id)
            ->orWhereHas('resolutionsHistory', fn ($query) => $query->whereKey($resolution->id))
            ->with(['resolution', 'resolutionsHistory'])
            ->get()
            ->each(fn ($association) => app(AssociationStateService::class)->sync($association));

        return new ReconocimientoResource($resolution->load([
            'state:id,title,abbreviation',
            'associations:id,code,name',
            'primaryAssociations:id,code,name,resolution_id',
        ]));
    }

    public function destroyReconocimiento(Resolution $resolution)
    {
        $hasPivot = DB::table('resolution_associations')
            ->where('resolution_id', $resolution->id)
            ->exists();

        $hasAssociations = Association::where('resolution_id', $resolution->id)->exists();
        $hasDirectives = Directive::where('resolution_id', $resolution->id)->exists();

        if ($hasPivot || $hasAssociations || $hasDirectives) {
            return response()->json([
                'message' => 'No se puede eliminar la resolución: tiene comités o directivas asociadas',
            ], 422);
        }

        $resolution->delete();

        return response()->json(null, 204);
    }

    // ==================== RESOLUCIÓN EXTERNA (PORTAL MUNICIPAL) ====================

    public function buscarResolucionExterna(Resolution $resolution)
    {
        $match = app(MunicipalResolutionService::class)->resolve($resolution);

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
        $match = app(MunicipalResolutionService::class)->resolve($resolution);

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
     * El portal municipal no entrega una cadena de certificados completa.
     * La excepción SSL queda limitada a este host externo conocido.
     */
    private function municipalHttpClient(int $timeout)
    {
        return Http::timeout($timeout)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->withoutVerifying();
    }

    // ==================== HELPERS ====================

    private function hydratePresidentData($associations): void
    {
        Association::hydratePresidents($associations);
    }

    private function setPresidentForSingle(Association $association): void
    {
        $presidenta = $association->getPresidenta();
        $association->president_partner_id = $presidenta?->id;
        $association->president_name = $presidenta && $presidenta->people
            ? trim($presidenta->people->names . ' ' . $presidenta->people->father_lastname)
            : null;
    }

    private function hydrateResolutions($associations): void
    {
        foreach ($associations as $association) {
            $resolutionsAll = collect();

            if ($association->resolution) {
                $resolutionsAll->push($association->resolution);
            }

            foreach ($association->resolutionsHistory as $res) {
                if ($res->id !== $association->resolution_id) {
                    $resolutionsAll->push($res);
                }
            }

            $resolutionsAll = $resolutionsAll->sortByDesc('date_start')->values();

            $association->allResolutions = $resolutionsAll->all();
            $association->latestResolution = $resolutionsAll->first();
        }
    }
}
