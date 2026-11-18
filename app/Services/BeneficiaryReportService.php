<?php

namespace App\Services;

use App\DTOs\BeneficiaryReportItemDTO;
use App\Models\Association;
use App\Models\Pecosa;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\PartnerRepositoryInterface;
use App\Repositories\Contracts\PecosaRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Collection;

class BeneficiaryReportService
{
    public function __construct(
        private PartnerRepositoryInterface $partnerRepo,
        private AssociationRepositoryInterface $associationRepo,
        private PecosaRepositoryInterface $pecosaRepo,
        private CatalogRepositoryInterface $catalogs,
        private PDFService $pdfService,
        private PadronGuardService $padronGuard,
        private MonthlyRosterService $monthlyRosters
    ) {
    }

    public function generatePadronReport(int $associationId, int $month, int $year): array
    {
        $association = $this->associationRepo->findWithPlaceSector($associationId)
            ?? throw (new ModelNotFoundException())->setModel(Association::class, [$associationId]);

        $officialRoster = $this->monthlyRosters->officialForPeriod($year, $month);
        if ($this->monthlyRosters->hasAny() && ! $officialRoster) {
            throw new \DomainException(
                'El padrón de '.sprintf('%02d/%04d', $month, $year)
                .' todavía no ha sido cerrado. Créelo y ciérrelo antes de generar el PDF.'
            );
        }

        // Un padrón ya cerrado es una fotografía histórica: una resolución
        // pendiente registrada después no puede volver a bloquear su emisión.
        if (! $officialRoster) {
            $this->padronGuard->assertCanGenerate($association, $year, $month);
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $cutoffDate = $endDate;
        [$pecosaStart, $pecosaEnd] = Pecosa::deliveryPeriodRange($year, $month);
        $rosterSnapshot = $this->monthlyRosters->associationSnapshot($year, $month, $associationId);

        if ($officialRoster && ! $rosterSnapshot) {
            throw new \DomainException('El comité seleccionado no forma parte del padrón cerrado de ese período.');
        }

        $presidenta = $rosterSnapshot?->president_name
            ?? $association->getPresidentNameAt($endDate->toDateString())
            ?? $association->getPresidentName();

        $partners = $rosterSnapshot
            ? collect()
            : $this->partnerRepo->findActiveByAssociation(
                $associationId,
                $startDate->toDateString(),
                $endDate->toDateString()
            );

        if (! $rosterSnapshot && $partners->isEmpty()) {
            throw new \DomainException('No hay socios vigentes para el comité y periodo seleccionado.');
        }

        $pecosa = $this->pecosaRepo->findByAssociationAndPeriod($associationId, $pecosaStart->toDateString(), $pecosaEnd->toDateString());

        [$beneficiarios, $resumen] = $rosterSnapshot
            ? $this->buildSnapshotReportData($rosterSnapshot->entries, $cutoffDate)
            : $this->buildReportData($partners, $cutoffDate, $startDate, $endDate);

        if (empty($beneficiarios)) {
            throw new \DomainException('No se encontraron beneficiarios vigentes para el comité y periodo seleccionados.');
        }

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
            'club_nombre' => mb_strtoupper($rosterSnapshot?->association_snapshot['name'] ?? $association->name, 'UTF-8'),
            'direccion' => $rosterSnapshot?->association_snapshot['address'] ?? $association->address ?? '',
            'ccpp' => $rosterSnapshot?->association_snapshot['sector_name'] ?? ($association->placeSector ? ($association->placeSector->sector ? $association->placeSector->sector->title : '') : ''),
            'presidenta' => $presidenta ?? 'SIN ASIGNAR',
            'zona' => $rosterSnapshot?->association_snapshot['zone_code'] ?? ($association->placeSector ? ($association->placeSector->place ? $association->placeSector->place->code : '01') : '01'),
            'comite' => $rosterSnapshot?->association_snapshot['code'] ?? $association->officialCodeAt($endDate),
            'num_mes' => $month,
            'periodo' => $periodo,
            'semestre' => $month <= 6 ? "{$year}-I" : "{$year}-II",
            'mes_nombre' => $meses[$month] ?? '',
            'anio' => $year,
            'total_beneficiarios' => collect($beneficiarios)->sum('rowspan'),
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s'),
            'productos_pecosa' => $pecosa ? $pecosa->detailPecosas : collect([]),
        ] + $this->catalogs->padronReportCatalogs();
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

                // Solo se incluyen beneficiarios con un historial vigente en el periodo
                // consultado; sin esto, el padrón mostraría siempre a todos los
                // beneficiarios sin importar el mes/año elegido.
                if (!$historialActivo) {
                    continue;
                }

                $tipoBeneficio = $historialActivo ? ($historialActivo->typeBenefit ? $historialActivo->typeBenefit->abbreviation : '') : '';
                $razonBaja = $historialActivo ? ($historialActivo->reasonDisqualification ? $historialActivo->reasonDisqualification->id : '') : '';
                $fechaInicio = $historialActivo ? $historialActivo->date_begin : null;
                $parentesco = $historialActivo && $historialActivo->relationship
                    ? $historialActivo->relationship->title
                    : ($beneficiario->relationship ? $beneficiario->relationship->title : '');

                $fum = $historialActivo->obstetricData?->date_last_menstrual_period;
                $referenciaGestacion = $fum ?: $fechaInicio;
                $mesesGestacion = $tipoBeneficio === 'GES'
                    ? ($referenciaGestacion ? Carbon::parse($referenciaGestacion)->diffInMonths($cutoffDate) : 999)
                    : null;
                $mesesLactancia = $tipoBeneficio === 'LAC'
                    ? ($fechaInicio ? Carbon::parse($fechaInicio)->diffInMonths($cutoffDate) : 999)
                    : null;

                [$bajaFlag, $observationFlag, $razonBaja] = $this->evaluateBeneficiaryRules($edadAnos, $tipoBeneficio, (string) $parentesco, $mesesGestacion, $mesesLactancia, $historialActivo, $razonBaja);

                $items[] = new BeneficiaryReportItemDTO(
                    mb_strtoupper("{$persona->father_lastname} {$persona->mother_lastname} {$persona->names}", 'UTF-8'),
                    $persona->dni ?? '',
                    in_array($tipoBeneficio, ['LAC', 'GES']) ? $tipoBeneficio : '',
                    $persona->birthdate ? date('d/m/Y', strtotime($persona->birthdate)) : '',
                    $persona->gender === 'M' ? 'M' : 'F',
                    $parentesco,
                    $edadAnos,
                    $edadMeses,
                    $edadDias,
                    !empty($razonBaja) && $razonBaja != 1,
                    $observationFlag,
                    $fechaInicio ? date('d/m/Y', strtotime($fechaInicio)) : '',
                    $historialActivo->date_end ? date('d/m/Y', strtotime($historialActivo->date_end)) : '',
                    $persona->id === $socia->id,
                    $mesesGestacion,
                    $mesesLactancia,
                );

                // Resumen counters
                $this->updateResumen($resumen, $persona, $tipoBeneficio, $edadAnos, $razonBaja);
            }

            if (!empty($items)) {
                $beneficiarios[] = [
                    'socia_nombre' => mb_strtoupper("{$socia->father_lastname} {$socia->mother_lastname} {$socia->names}", 'UTF-8'),
                    'socia_direccion' => $socia->address ?? '',
                    'socia_dni' => $socia->dni ?? '',
                    'rowspan' => count($items),
                    'items' => $items,
                ];
            }
        }

        return [$beneficiarios, $resumen];
    }

    private function buildSnapshotReportData(Collection $entries, Carbon $cutoffDate): array
    {
        $keys = ['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'];
        $resumen = [
            'total' => array_fill_keys($keys, 0),
            'masculino' => array_fill_keys($keys, 0),
            'femenino' => array_fill_keys($keys, 0),
        ];
        $beneficiarios = [];

        foreach ($entries->groupBy('partner_id') as $group) {
            $partner = $group->first()->partner_snapshot;
            $items = [];

            foreach ($group as $entry) {
                $person = $entry->beneficiary_snapshot;
                $birth = ! empty($person['birthdate']) ? Carbon::parse($person['birthdate']) : null;
                $age = $birth ? $birth->diffInYears($cutoffDate) : 0;
                $ageInterval = $birth?->diff($cutoffDate);
                $type = (string) ($person['benefit_type'] ?? '');
                $relationship = (string) ($person['relationship'] ?? '');
                $dateBegin = $person['date_begin'] ?? null;
                $fum = $person['fum'] ?? null;
                $monthsGestation = $type === 'GES' && ($fum || $dateBegin)
                    ? Carbon::parse($fum ?: $dateBegin)->diffInMonths($cutoffDate)
                    : null;
                $monthsBreastfeeding = $type === 'LAC' && $dateBegin
                    ? Carbon::parse($dateBegin)->diffInMonths($cutoffDate)
                    : null;
                $history = (object) ['reason_disqualification_id' => $person['reason_disqualification_id'] ?? null];
                [$isDisqualified, $observation, $reason] = $this->evaluateBeneficiaryRules(
                    $age,
                    $type,
                    $relationship,
                    $monthsGestation,
                    $monthsBreastfeeding,
                    $history,
                    $person['reason_disqualification_id'] ?? null
                );

                $items[] = new BeneficiaryReportItemDTO(
                    mb_strtoupper($this->snapshotName($person), 'UTF-8'),
                    $person['dni'] ?? '',
                    in_array($type, ['LAC', 'GES'], true) ? $type : '',
                    $birth?->format('d/m/Y') ?? '',
                    ($person['gender'] ?? 'F') === 'M' ? 'M' : 'F',
                    $relationship,
                    $age,
                    $ageInterval?->m ?? 0,
                    $ageInterval?->d ?? 0,
                    $isDisqualified || (! empty($reason) && (int) $reason !== 1),
                    $observation,
                    $dateBegin ? Carbon::parse($dateBegin)->format('d/m/Y') : '',
                    ! empty($person['date_end']) ? Carbon::parse($person['date_end'])->format('d/m/Y') : '',
                    ($person['dni'] ?? null) === ($partner['dni'] ?? null),
                    $monthsGestation,
                    $monthsBreastfeeding,
                );

                $this->updateResumen(
                    $resumen,
                    (object) ['gender' => $person['gender'] ?? 'F'],
                    $type,
                    $age,
                    $reason
                );
            }

            if ($items) {
                $beneficiarios[] = [
                    'socia_nombre' => mb_strtoupper($this->snapshotName($partner), 'UTF-8'),
                    'socia_direccion' => $partner['address'] ?? '',
                    'socia_dni' => $partner['dni'] ?? '',
                    'rowspan' => count($items),
                    'items' => $items,
                ];
            }
        }

        return [$beneficiarios, $resumen];
    }

    private function snapshotName(array $snapshot): string
    {
        return trim(collect([
            $snapshot['father_lastname'] ?? null,
            $snapshot['mother_lastname'] ?? null,
            $snapshot['names'] ?? null,
        ])->filter()->implode(' '));
    }

    private const PARENTESCOS_MENOR = ['hijo', 'hijos', 'hija', 'nieto', 'nieta', 'apoderado', 'tutelado'];

    private function evaluateBeneficiaryRules(int $edadAnos, string $tipoBeneficio, string $parentesco, ?int $mesesGestacion, ?int $mesesLactancia, $historialActivo, $razonBaja): array
    {
        $observationFlag = false;
        $bajaFlag = false;
        $sinBajaPrevia = $historialActivo
            && (!$historialActivo->reason_disqualification_id || $historialActivo->reason_disqualification_id == 1);

        // Baja por edad: solo menores dependientes (hijo, nieto, apoderado/tutelado)
        // que no son gestantes ni lactantes.
        $esMenorDependiente = in_array(mb_strtolower(trim($parentesco), 'UTF-8'), self::PARENTESCOS_MENOR, true);
        if ($edadAnos >= 14 && $esMenorDependiente && !in_array($tipoBeneficio, ['GES', 'LAC'], true) && $sinBajaPrevia) {
            $razonBaja = 4; $bajaFlag = true;
        }

        if ($tipoBeneficio === 'GES' && ($mesesGestacion === null || $mesesGestacion > 9) && $sinBajaPrevia) {
            $razonBaja = 4; $bajaFlag = true;
        }

        if ($tipoBeneficio === 'LAC' && ($mesesLactancia === null || $mesesLactancia > 24) && $sinBajaPrevia) {
            $razonBaja = 4; $bajaFlag = true;
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
        $parentescosMenor = self::PARENTESCOS_MENOR;
        $socias = fn($b) => $b->esSocia && $b->sexo === 'F' && $b->edadAnos >= 12;

        return [
            [
                'codigo' => 1,
                'descripcion' => 'EDAD >= 14 años (BAJA) - HIJO / NIETO / APODERADO (TUTELADO)',
                'cantidad' => $todos->filter(fn($b) => $b->esBaja
                    && $b->edadAnos >= 14
                    && in_array(mb_strtolower($b->parentesco, 'UTF-8'), $parentescosMenor, true))->count(),
            ],
            [
                'codigo' => 2,
                'descripcion' => 'GES. SOCIA Y BENEFICIARIA >= 12 AÑOS, MAS DE 9 MESES DE SU ULTIMA MENSTRUACION (BAJA)',
                'cantidad' => $todos->filter(fn($b) => $b->tipo === 'GES' && $socias($b)
                    && ($b->mesesGestacion === null || $b->mesesGestacion > 9))->count(),
            ],
            [
                'codigo' => 3,
                'descripcion' => 'LAC. SOCIA Y BENEFICIARIA >= 12 AÑOS, MAS DE 2 AÑOS DE INICIADO SU BENEFICIO (BAJA)',
                'cantidad' => $todos->filter(fn($b) => $b->tipo === 'LAC' && $socias($b)
                    && ($b->mesesLactancia === null || $b->mesesLactancia > 24))->count(),
            ],
        ];
    }
}
