<?php

namespace App\Services\KnowledgeBase;

use App\Services\Alertas\VencimientoAlertService;
use App\Services\Normativa\NormativaScraperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Consulta en segundo plano al portal (normativa) y recalcula los avisos de
 * vencimiento. Se dispara tras el login desde el navegador, en una petición
 * aparte, para no retrasar la autenticación. Una cerradura de caché evita
 * ejecuciones simultáneas o repetidas dentro del intervalo.
 */
class KnowledgeSyncService
{
    private const LOCK_KEY = 'knowledge-sync:running';
    private const LAST_RUN_KEY = 'knowledge-sync:last-run';

    public function __construct(
        private NormativaScraperService $scraper,
        private VencimientoAlertService $alerts,
        private KnowledgeChangeLogService $changeLog,
    ) {
    }

    /** @return array{status:string, normativa?:array, alertas?:array, last_run?:string|null} */
    public function runIfDue(?int $userId = null): array
    {
        $interval = max(1, (int) config('alertas.sync_interval_minutes', 180));
        $last = Cache::get(self::LAST_RUN_KEY);
        if ($last && now()->diffInMinutes($last) < $interval) {
            return ['status' => 'fresh', 'last_run' => $last->toIso8601String()];
        }

        $lock = Cache::lock(self::LOCK_KEY, 600);
        if (! $lock->get()) {
            return ['status' => 'running'];
        }

        try {
            @set_time_limit(600);
            $out = ['status' => 'done'];

            try {
                $out['normativa'] = $this->scraper->scanAndNotify();
            } catch (Throwable $e) {
                Log::warning('Sincronización de normativa fallida.', ['error' => $e->getMessage()]);
                $out['normativa'] = ['error' => true];
            }

            try {
                $out['alertas'] = $this->alerts->scanAndNotify();
            } catch (Throwable $e) {
                Log::warning('Sincronización de alertas fallida.', ['error' => $e->getMessage()]);
                $out['alertas'] = ['error' => true];
            }

            Cache::forever(self::LAST_RUN_KEY, now());
            $out['last_run'] = now()->toIso8601String();

            $this->changeLog->record(
                source: 'sistema',
                eventType: 'sincronizacion',
                title: 'Sincronización con el portal y alertas',
                summary: sprintf(
                    'Normas nuevas relevantes: %d. Avisos emitidos: %d.',
                    $out['normativa']['relevantes'] ?? 0,
                    ($out['alertas']['edad'] ?? 0) + ($out['alertas']['beneficios'] ?? 0) + ($out['alertas']['resoluciones'] ?? 0),
                ),
                details: ['normativa' => $out['normativa'], 'alertas' => $out['alertas']],
                triggeredBy: $userId,
            );

            return $out;
        } finally {
            $lock->release();
        }
    }
}
