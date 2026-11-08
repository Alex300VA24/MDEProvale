<?php

namespace App\Services\Alertas;

use App\Models\Association;
use App\Models\Notification;
use App\Models\State;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\AssociationStateService;
use Illuminate\Support\Carbon;

/**
 * Simula el fin de la resolución de un comité sin modificar sus datos: se
 * adelanta la "fecha de hoy" hasta después del término y se emite el aviso
 * real (marcado como simulación) en dos variantes: consulta normal y con IA.
 */
class VencimientoSimulationService
{
    public const MODE_NORMAL = 'normal';
    public const MODE_IA = 'ia';

    public function __construct(
        private AssociationRepositoryInterface $associations,
        private AssociationStateService $states,
        private VencimientoAlertService $alerts,
        private NotificationRepositoryInterface $notifications,
    ) {
    }

    /**
     * @param  array<int,string>  $modes  MODE_NORMAL y/o MODE_IA
     * @param  int  $daysLeft  días restantes simulados (negativo = ya vencida)
     * @return array{committee:Association, resolution:\App\Models\Resolution, today:Carbon, state_before:string, state_after:string, results:array<int,array{mode:string, notification:Notification, ia_ok:bool}>}
     *
     * @throws \DomainException si el comité no existe, es ambiguo o no tiene resolución con fecha de término
     */
    public function simulate(string $committeeName, array $modes, int $daysLeft = -1): array
    {
        $committee = $this->findCommittee($committeeName);
        $resolution = $this->resolutionOf($committee);
        $today = $resolution->date_end->copy()->startOfDay()->subDays($daysLeft);

        $alert = $this->alerts
            ->detectResolutionAlerts($today, $committee->id)
            ->first(fn (array $a) => ($a['metadata']['resolution_id'] ?? null) === $resolution->id);

        if (! $alert) {
            throw new \DomainException('Con ese escenario la resolución queda fuera de la ventana de avisos (revisa config/alertas.php).');
        }

        $results = [];
        foreach ($modes as $mode) {
            $simulated = $this->asSimulation($alert, $mode);
            $this->notifications->deleteByDedupeKey(VencimientoAlertService::TYPE_RESOLUCION, $simulated['dedupe_key']);

            $notification = $this->alerts->publish($simulated, VencimientoAlertService::TYPE_RESOLUCION, $mode === self::MODE_IA);
            $results[] = [
                'mode' => $mode,
                'notification' => $notification,
                'ia_ok' => ! empty($notification->metadata['ia']),
            ];
        }

        return [
            'committee' => $committee,
            'resolution' => $resolution,
            'today' => $today,
            'state_before' => $this->states->abbreviationFor($committee),
            'state_after' => $this->states->abbreviationFor($committee, $today),
            'results' => $results,
        ];
    }

    /** Elimina los avisos de simulación del comité; devuelve cuántos. */
    public function clear(string $committeeName): int
    {
        $committee = $this->findCommittee($committeeName);
        $resolution = $this->resolutionOf($committee);
        $baseKey = $this->baseKey($resolution->id, $resolution->date_end->toDateString());

        $deleted = 0;
        foreach ([self::MODE_NORMAL, self::MODE_IA] as $mode) {
            foreach (['vencida', '7', '30', '60'] as $stage) {
                $deleted += $this->notifications->deleteByDedupeKey(
                    VencimientoAlertService::TYPE_RESOLUCION,
                    "{$baseKey}:{$stage}:sim-{$mode}"
                );
            }
        }

        return $deleted;
    }

    private function asSimulation(array $alert, string $mode): array
    {
        $alert['dedupe_key'] .= ':sim-'.$mode;
        $alert['title'] = '[SIMULACIÓN '.($mode === self::MODE_IA ? 'IA' : 'NORMAL').'] '.$alert['title'];
        $alert['metadata']['simulacion'] = $mode;

        return $alert;
    }

    private function baseKey(int $resolutionId, string $dateEnd): string
    {
        return 'resolucion:'.$resolutionId.':'.$dateEnd;
    }

    private function findCommittee(string $name): Association
    {
        $matches = $this->associations->searchByName($name);

        if ($matches->isEmpty()) {
            throw new \DomainException("No existe un comité cuyo nombre contenga «{$name}».");
        }

        $exact = $matches->first(fn (Association $a) => mb_strtolower(trim($a->name)) === mb_strtolower(trim($name)));
        if ($exact) {
            return $exact;
        }

        if ($matches->count() > 1) {
            throw new \DomainException(
                'Hay varios comités con ese nombre: '.$matches->pluck('name')->implode(', ').'. Escribe el nombre completo.'
            );
        }

        return $matches->first();
    }

    private function resolutionOf(Association $committee): \App\Models\Resolution
    {
        $resolution = $committee->resolution;

        if (! $resolution || ! $resolution->date_end) {
            throw new \DomainException("El comité «{$committee->name}» no tiene una resolución con fecha de término.");
        }

        return $resolution;
    }
}
