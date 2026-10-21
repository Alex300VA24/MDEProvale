<?php

namespace App\Services;

use App\Repositories\Contracts\AssociationRepositoryInterface;
use Carbon\Carbon;

/**
 * Datos de los reportes del Club de Madres: padrón histórico y acta de reuniones.
 */
class ClubReportService
{
    private const MESES = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
        'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

    public function __construct(private AssociationRepositoryInterface $associations)
    {
    }

    /**
     * Padrón de Club de Madres con resoluciones de reconocimiento, histórico:
     * comités, presidenta y beneficiarios vigentes en el mes/año consultado.
     */
    public function padron(int $mes, int $anio): array
    {
        $startDate = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $rosterPeriods = $this->associations->rosterPeriodsForMonth($start);

        // Entre marzo y setiembre de 2026 manda la presencia real en los
        // padrones mensuales, incluso si la resolución está pendiente.
        $associations = $this->associations->forPadron(
            $rosterPeriods->isNotEmpty() ? $rosterPeriods->keys() : null,
            $start,
            $end
        );

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

            $rosterPeriod = $rosterPeriods->get($association->id);

            // El encabezado del Excel conserva incluso presidentas que no
            // aparecían como socias y por ello no pudieron ligarse a directivas.
            $presidenta = $rosterPeriod
                ? ($rosterPeriod->president_name ?? '')
                : ($association->getPresidentNameAt($end) ?? '');

            $totalBenef = $rosterPeriod
                ? (int) $rosterPeriod->beneficiary_count
                : $this->associations->beneficiaryCountInPeriod($association->id, $start, $end);

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
                return $r->date_start && Carbon::parse($r->date_start)->lte($endDate);
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
                'nombre'       => mb_strtoupper($association->name, 'UTF-8'),
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

            $rs = mb_strtoupper(trim($association->company_name ?? ''), 'UTF-8');
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
                $rsKey = mb_strtoupper(trim($rs), 'UTF-8');
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

        return [
            'zonas'             => array_values($zonaGroups),
            'totales_generales' => $totalesGenerales,
            'totales_rs'        => $totalesRS,
            'mes_nombre'        => self::MESES[$mes] ?? '',
            'anio'              => $anio,
            'periodo'           => self::MESES[$mes].' '.$anio,
            'fecha'             => date('d/m/Y'),
            'hora'              => date('H:i:s'),
        ];
    }

    /**
     * Padrón de asistencia: comités con su presidenta vigente precargada y la
     * columna de firma en blanco para el registro manual durante la reunión.
     */
    public function actaReuniones(): array
    {
        $associations = $this->associations->withResolutionAndLocation()
            ->sortBy(function ($association) {
                return sprintf(
                    '%03d-%s',
                    optional(optional($association->placeSector)->place)->id ?? 999,
                    $association->code
                );
            })
            ->values();

        $numero = 1;
        $filas = $associations->map(function ($association) use (&$numero) {
            $presidenta = $association->getPresidenta();
            $people = $presidenta ? $presidenta->people : null;
            $zonaId = optional(optional($association->placeSector)->place)->id;

            return [
                'zona'       => $zonaId ? str_pad($zonaId, 2, '0', STR_PAD_LEFT) : '',
                'comite'     => $association->code ?? '',
                'numero'     => $numero++,
                'club'       => mb_strtoupper($association->name, 'UTF-8'),
                'presidenta' => $people
                    ? mb_strtoupper(trim("{$people->names} {$people->father_lastname} {$people->mother_lastname}"), 'UTF-8')
                    : '',
                'dni'        => $people->dni ?? '',
            ];
        })->all();

        return [
            'filas'    => $filas,
            'generado' => now()->format('d/m/Y H:i'),
        ];
    }
}
