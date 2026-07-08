<?php

namespace App\Services;

use App\DTOs\BeneficiaryReportItemDTO;
use App\Models\Association;
use App\Models\Pecosa;
use App\Models\Partner;
use App\Models\ReasonDisqualification;
use App\Models\Relationship;
use App\Models\State;
use App\Models\TypeBenefit;
use App\Repositories\PartnerRepository;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Collection;

class BeneficiaryReportService
{
    private PartnerRepository $partnerRepo;
    private PDFService $pdfService;

    public function __construct(PartnerRepository $partnerRepo, PDFService $pdfService)
    {
        $this->partnerRepo = $partnerRepo;
        $this->pdfService = $pdfService;
    }

    public function generatePadronReport(int $associationId, int $month, int $year): array
    {
        $association = Association::with(['placeSector.place', 'placeSector.sector'])->findOrFail($associationId);
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $cutoffDate = $endDate;

        $presidenta = $association->getPresidentNameAt($endDate->toDateString())
            ?? $association->getPresidentName();

        $partners = $this->partnerRepo->findActiveByAssociation($associationId, $endDate->toDateString());

        if ($partners->isEmpty()) {
            throw new \DomainException('No hay socios vigentes para el comité y periodo seleccionado.');
        }

        $pecosa = Pecosa::with('detailPecosas.detailProduct.product')
            ->where('association_id', $associationId)
            ->whereBetween('delivery_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->first();

        [$beneficiarios, $resumen] = $this->buildReportData($partners, $cutoffDate, $startDate, $endDate);
        $observaciones = $this->buildObservaciones($beneficiarios, $cutoffDate);

        $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SETIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $periodo = $year . '-' . ($month <= 6 ? 'I' : 'II');

        $resumenFilas = [
            ['label' => 'MASCULINO', 'data' => $resumen['masculino']],
            ['label' => 'FEMENINO',  'data' => $resumen['femenino']],
            ['label' => 'TOTAL',     'data' => $resumen['total']],
        ];

        return [
            'beneficiarios' => $beneficiarios,
            'resumen' => $resumen,
            'resumen_filas' => $resumenFilas,
            'observaciones' => $observaciones,
            'club_nombre' => strtoupper($association->name),
            'direccion' => $association->address ?? '',
            'ccpp' => $association->placeSector ? ($association->placeSector->sector ? $association->placeSector->sector->title : '') : '',
            'direccion' => $association->address ?? '',
            'presidenta' => $presidenta ?? 'SIN ASIGNAR',
            'zona' => $association->placeSector ? ($association->placeSector->place ? $association->placeSector->place->code : '01') : '01',
            'comite' => $association->code ?? $association->id,
            'num_mes' => $month,
            'periodo' => $periodo,
            'semestre' => $month <= 6 ? "{$year}-I" : "{$year}-II",
            'mes_nombre' => $meses[$month] ?? '',
            'anio' => $year,
            'total_beneficiarios' => collect($beneficiarios)->sum('rowspan'),
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s'),
            'productos_pecosa' => $pecosa ? $pecosa->detailPecosas : collect([]),
            'parentescos' => Relationship::orderBy('id')->get(['id', 'title'])->toArray(),
            'tipo_beneficios' => TypeBenefit::orderBy('id')->get(['id', 'title', 'abbreviation'])->toArray(),
            'bajas' => ReasonDisqualification::orderBy('id')->get(['id', 'title'])->toArray(),
        ];
    }

    private function buildReportData(Collection $partners, Carbon $cutoffDate, Carbon $startDate, Carbon $endDate): array
    {
        $beneficiarios = [];
        $resumen = [
            'total' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'masculino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'femenino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
        ];

        foreach ($partners as $partner) {
            $socia = $partner->people;
            if (!$socia) continue;
            $items = [];

            foreach ($partner->beneficiaries as $beneficiario) {
                $persona = $beneficiario->person;
                if (!$persona) continue;

                $edadAnos = $persona->birthdate ? Carbon::parse($persona->birthdate)->diffInYears($cutoffDate) : 0;
                $edadMeses = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff($cutoffDate)->m : 0;
                $edadDias = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff($cutoffDate)->d : 0;

                $historialActivo = $beneficiario->histories
                    ->whereNotNull('state_id')
                    ->filter(fn($h) => $h->date_begin && Carbon::parse($h->date_begin)->lte($endDate)
                        && (!$h->date_end || Carbon::parse($h->date_end)->gte($startDate)))
                    ->sortByDesc('date_begin')
                    ->first();

                $tipoBeneficio = $historialActivo ? ($historialActivo->typeBenefit ? $historialActivo->typeBenefit->abbreviation : '') : '';
                $razonBaja = $historialActivo ? ($historialActivo->reasonDisqualification ? $historialActivo->reasonDisqualification->id : '') : '';
                $fechaInicio = $historialActivo ? $historialActivo->date_begin : null;

                [$bajaFlag, $observationFlag, $razonBaja] = $this->evaluateBeneficiaryRules($edadAnos, $tipoBeneficio, $fechaInicio, $cutoffDate, $historialActivo, $razonBaja);

                $items[] = new BeneficiaryReportItemDTO(
                    strtoupper("{$persona->father_lastname} {$persona->mother_lastname} {$persona->names}"),
                    $persona->dni ?? '',
                    in_array($tipoBeneficio, ['LAC', 'GES']) ? $tipoBeneficio : '',
                    $persona->birthdate ? date('d/m/Y', strtotime($persona->birthdate)) : '',
                    $persona->gender === 'M' ? 'M' : 'F',
                    $beneficiario->relationship ? $beneficiario->relationship->title : '',
                    $edadAnos,
                    $edadMeses,
                    $edadDias,
                    !empty($razonBaja) && $razonBaja != 1,
                    $observationFlag,
                    $fechaInicio ? date('d/m/Y', strtotime($fechaInicio)) : '',
                );

                // Resumen counters
                $this->updateResumen($resumen, $persona, $tipoBeneficio, $edadAnos, $razonBaja);
            }

            if (!empty($items)) {
                $beneficiarios[] = [
                    'socia_nombre' => strtoupper("{$socia->father_lastname} {$socia->mother_lastname} {$socia->names}"),
                    'socia_direccion' => $socia->address ?? '',
                    'socia_dni' => $socia->dni ?? '',
                    'rowspan' => count($items),
                    'items' => $items,
                ];
            }
        }

        return [$beneficiarios, $resumen];
    }

    private function evaluateBeneficiaryRules(int $edadAnos, string $tipoBeneficio, ?string $fechaInicio, Carbon $cutoffDate, $historialActivo, $razonBaja): array
    {
        $observationFlag = false;
        $bajaFlag = false;

        if ($edadAnos >= 14 && $historialActivo && !$historialActivo->reason_disqualification_id) {
            $razonBaja = 4; $bajaFlag = true;
        }

        if ($tipoBeneficio === 'GES') {
            $mesesTotales = $fechaInicio ? Carbon::parse($fechaInicio)->diffInMonths($cutoffDate) : 999;
            if (!$fechaInicio || $mesesTotales > 9) {
                if ($historialActivo && (!$historialActivo->reason_disqualification_id || $historialActivo->reason_disqualification_id == 1)) {
                    $razonBaja = 4; $bajaFlag = true;
                }
            }
        }

        if ($tipoBeneficio === 'LAC') {
            $mesesTotales = $fechaInicio ? Carbon::parse($fechaInicio)->diffInMonths($cutoffDate) : 999;
            if (!$fechaInicio || $mesesTotales > 12) {
                if ($historialActivo && (!$historialActivo->reason_disqualification_id || $historialActivo->reason_disqualification_id == 1)) {
                    $razonBaja = 4; $bajaFlag = true;
                }
            }
        }

        if (in_array($tipoBeneficio, ['GES', 'LAC']) && $edadAnos <= 12) {
            $observationFlag = true;
        }

        return [$bajaFlag, $observationFlag, $razonBaja];
    }

    private function updateResumen(array &$resumen, $persona, string $tipoBeneficio, int $edadAnos, $razonBaja): void
    {
        $gender = $persona->gender === 'M' ? 'masculino' : 'femenino';

        if ($edadAnos <= 6) {
            $key = $edadAnos == 1 ? '1_ano' : "{$edadAnos}_anos";
            foreach (['total', $gender] as $g) {
                $resumen[$g][$key]++;
                $resumen[$g]['total']++;
            }
        }

        if ($edadAnos >= 7 && $edadAnos <= 13) {
            foreach (['total', $gender] as $g) $resumen[$g]['ninos_7_13']++;
        }

        $typeMap = ['GES' => 'madres_gestantes', 'LAC' => 'madres_lactantes', 'ADU' => 'ancianos', 'TBC' => 'tuberculosos'];
        if (isset($typeMap[$tipoBeneficio])) {
            foreach (['total', $gender] as $g) $resumen[$g][$typeMap[$tipoBeneficio]]++;
        }

        if (!empty($razonBaja) && $razonBaja != 1) {
            foreach (['total', $gender] as $g) $resumen[$g]['gap']++;
        }

        foreach (['total', $gender] as $g) $resumen[$g]['total_general']++;
    }

    private function buildObservaciones(array $beneficiarios, Carbon $cutoffDate): array
    {
        $todos = collect($beneficiarios)->flatMap(fn($g) => $g['items']);

        return [
            [
                'codigo' => 1,
                'descripcion' => 'EDAD >= 14 años (BAJA)',
                'cantidad' => $todos->filter(fn($b) => !empty($b->beneficiario_baja))->count(),
            ],
            [
                'codigo' => 2,
                'descripcion' => 'GES / LAC <= DE 12 AÑOS',
                'cantidad' => $todos->filter(fn($b) => in_array($b ? $b->tipo : '', ['GES', 'LAC']) && $b->edadAnos <= 12)->count(),
            ],
        ];
    }
}