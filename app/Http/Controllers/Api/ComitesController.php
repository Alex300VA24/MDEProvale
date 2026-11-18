<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsignarCargoRequest;
use App\Http\Requests\AsignarCargosRequest;
use App\Http\Requests\RegularizeReconocimientoRequest;
use App\Http\Requests\StoreClubRequest;
use App\Http\Requests\StoreReconocimientoRequest;
use App\Http\Requests\UpdateClubRequest;
use App\Http\Requests\UpdateReconocimientoRequest;
use App\Http\Resources\ClubResource;
use App\Http\Resources\ReconocimientoResource;
use App\Models\Association;
use App\Models\Resolution;
use App\Models\State;
use App\Services\CommitteeService;
use App\Exceptions\MunicipalPortalException;
use App\Services\MunicipalResolutionService;
use App\Services\ResolutionService;
use App\Services\UploadSettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComitesController extends Controller
{
    public function __construct(
        private CommitteeService $committees,
        private ResolutionService $resolutions
    ) {
    }

    // ==================== COMITÉS (CLUB DE MADRES) ====================

    public function clubs(Request $request)
    {
        $associations = $this->committees->paginate(
            $request->only(['search', 'state_id', 'place_id', 'sector_id', 'place_sector_id']),
            (int) $request->input('per_page', 10)
        );

        return ClubResource::collection($associations);
    }

    public function clubsOptions(Request $request)
    {
        $includeId = $request->filled('include_resolution_id') ? (int) $request->input('include_resolution_id') : null;

        return response()->json($this->committees->options($includeId));
    }

    public function club(Association $association)
    {
        return new ClubResource($this->committees->show($association));
    }

    public function storeClub(StoreClubRequest $request)
    {
        return (new ClubResource($this->committees->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function updateClub(UpdateClubRequest $request, Association $association)
    {
        return new ClubResource($this->committees->update($association, $request->validated()));
    }

    public function changeClubStatus(Request $request, Association $association, UploadSettingsService $uploadSettings)
    {
        $data = $request->validate([
            'state_id' => [
                'required',
                Rule::exists('states', 'id')->where(fn ($query) => $query->whereIn('abbreviation', [State::CURRENT, State::INACTIVE])),
            ],
            'reason' => ['required', Rule::in(['RENUNCIA', 'OTRO'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'effective_date' => ['required', 'date'],
            'acta' => ['nullable', 'file', 'mimes:pdf', 'max:'.$uploadSettings->maxKilobytes()],
        ]);

        $association = $this->committees->changeStatus($association, $data, $request->file('acta'), $request->user()?->id);

        return new ClubResource($association);
    }

    public function restoreAutomaticClubStatus(Association $association)
    {
        return new ClubResource($this->committees->restoreAutomaticStatus($association));
    }

    public function destroyClub(Association $association)
    {
        try {
            $this->committees->delete($association);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    /**
     * Asigna un cargo de la directiva a una socia del comité.
     */
    public function asignarCargo(AsignarCargoRequest $request, Association $association)
    {
        $validated = $request->validated();

        try {
            $association = $this->committees->assignPosition($association, (int) $validated['partner_id'], $validated['position']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al asignar el cargo: '.$e->getMessage()], 422);
        }

        return new ClubResource($association);
    }

    /**
     * Guarda varios cargos de la directiva en una sola operación.
     */
    public function asignarCargos(AsignarCargosRequest $request, Association $association)
    {
        try {
            $association = $this->committees->assignPositions($association, collect($request->validated('assignments')));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al asignar los cargos: '.$e->getMessage()], 422);
        }

        return new ClubResource($association);
    }

    // ==================== RECONOCIMIENTOS (RESOLUCIONES) ====================

    public function reconocimientos(Request $request)
    {
        $resolutions = $this->resolutions->paginate(
            $request->only(['search', 'state_id', 'anio', 'mes']),
            (int) $request->input('per_page', 10)
        );

        return ReconocimientoResource::collection($resolutions);
    }

    public function reconocimientosOptions()
    {
        return response()->json($this->resolutions->options());
    }

    public function storeReconocimiento(StoreReconocimientoRequest $request, MunicipalResolutionService $municipal)
    {
        $data = $request->validated();
        $document = trim($data['document']);
        $year = now()->year;

        if (! preg_match('/^\d{4}-'.preg_quote((string) $year, '/').'$/', $document)) {
            return response()->json([
                'success' => false,
                'reason' => 'invalid_format',
                'message' => 'El documento "'.$document.'" debe tener el formato 1234-'.$year.'.',
                'hint' => 'Ingrese 4 dígitos, un guion y el año actual.',
                'retryable' => false,
            ], 422);
        }

        $data['document'] = $document;

        // Sin Pendiente: el número debe existir en el portal municipal para guardar.
        if (! ($data['pending'] ?? false)) {
            try {
                $match = $municipal->resolveByDocument($document);
            } catch (MunicipalPortalException $e) {
                return response()->json($e->toPayload(), $e->httpStatus());
            }

            if (! $match) {
                return response()->json([
                    'success' => false,
                    'reason' => 'not_found',
                    'message' => 'La resolución '.$document.' no existe en el portal de la Municipalidad de La Esperanza.',
                    'hint' => 'Verifica el número o marca la resolución como Pendiente para guardarla sin validación.',
                    'retryable' => false,
                ], 422);
            }
        }

        try {
            $resolution = $this->resolutions->create($data);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new ReconocimientoResource($resolution))
            ->response()
            ->setStatusCode(201);
    }

    /** Pendiente -> Vigente: valida que el número exista en el portal municipal. */
    public function regularizeReconocimiento(RegularizeReconocimientoRequest $request, Resolution $resolution)
    {
        try {
            return new ReconocimientoResource($this->resolutions->regularize(
                $resolution,
                $request->validated('document'),
                array_filter($request->safe()->only(['date_start', 'date_end']))
            ));
        } catch (MunicipalPortalException $e) {
            return response()->json($e->toPayload(), $e->httpStatus());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateReconocimiento(UpdateReconocimientoRequest $request, Resolution $resolution)
    {
        return new ReconocimientoResource($this->resolutions->update($resolution, $request->validated()));
    }

    public function destroyReconocimiento(Resolution $resolution)
    {
        try {
            $this->resolutions->delete($resolution);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    // ==================== RESOLUCIÓN EXTERNA (PORTAL MUNICIPAL) ====================

    /** Consulta previa al registro: valida el número contra el portal sin persistir. */
    public function buscarResolucionExternaPorNumero(Request $request, MunicipalResolutionService $municipal)
    {
        $request->validate([
            'document' => 'required|string|max:100',
        ], [
            'document.required' => 'Ingrese el número de resolución a verificar.',
        ]);

        $document = trim($request->input('document'));

        $year = now()->year;
        if (! preg_match('/^\d{4}-'.preg_quote((string) $year, '/').'$/', $document)) {
            return response()->json([
                'success' => false,
                'reason' => 'invalid_format',
                'message' => 'El documento "'.$document.'" debe tener el formato 1234-'.$year.'.',
                'hint' => 'Corrige el número o marca la resolución como Pendiente.',
                'retryable' => false,
            ], 422);
        }

        try {
            $match = $municipal->resolveByDocument($document);
        } catch (MunicipalPortalException $e) {
            return response()->json($e->toPayload(), $e->httpStatus());
        }

        if (! $match) {
            return response()->json([
                'success' => false,
                'reason' => 'not_found',
                'message' => 'La resolución '.$document.' no existe en el portal de la Municipalidad de La Esperanza.',
                'hint' => 'Verifica el número o marca la resolución como Pendiente.',
                'retryable' => false,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'titulo' => $match['titulo'],
            'fecha' => $match['fecha'],
        ]);
    }

    public function buscarResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal)
    {
        try {
            $match = $municipal->resolve($resolution);
        } catch (MunicipalPortalException $e) {
            return response()->json($e->toPayload(), $e->httpStatus());
        }

        if (! $match) {
            return response()->json([
                'success' => false,
                'reason' => 'not_found',
                'message' => 'No se encontró esta resolución en el portal de la Municipalidad de La Esperanza.',
                'hint' => 'El portal respondió con normalidad, pero la resolución no figura publicada. Verifica el número y año del documento.',
                'retryable' => false,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'titulo' => $match['titulo'],
            'fecha' => $match['fecha'],
        ]);
    }

    public function previewResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal)
    {
        return $this->streamResolucionExterna($resolution, $municipal, 'inline');
    }

    public function descargarResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal)
    {
        return $this->streamResolucionExterna($resolution, $municipal, 'attachment');
    }

    private function streamResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal, string $disposition)
    {
        try {
            $pdf = $municipal->fetchPdf($resolution);
        } catch (MunicipalPortalException $e) {
            return response()->json($e->toPayload(), $e->httpStatus());
        }

        if (! $pdf) {
            return response()->json([
                'success' => false,
                'reason' => 'not_found',
                'message' => 'No se encontró esta resolución en el portal de la Municipalidad.',
                'retryable' => false,
            ], 404);
        }

        return response($pdf['body'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$pdf['filename'].'"',
        ]);
    }
}
