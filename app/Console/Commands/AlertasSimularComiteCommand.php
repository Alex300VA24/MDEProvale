<?php

namespace App\Console\Commands;

use App\Models\State;
use App\Services\Alertas\VencimientoSimulationService;
use Illuminate\Console\Command;

class AlertasSimularComiteCommand extends Command
{
    protected $signature = 'alertas:simular-comite
        {nombre="las mejores" : Nombre (o parte) del comité}
        {--modo=ambos : normal, ia o ambos}
        {--dias=-1 : Días restantes simulados de la resolución (negativo = ya vencida)}
        {--limpiar : Elimina los avisos de simulación del comité y termina}';

    protected $description = 'Simula el fin de la resolución de un comité y emite el aviso (consulta normal y/o con IA) sin tocar sus datos';

    public function handle(VencimientoSimulationService $simulation): int
    {
        $name = (string) $this->argument('nombre');

        try {
            if ($this->option('limpiar')) {
                $this->info('Avisos de simulación eliminados: '.$simulation->clear($name));

                return self::SUCCESS;
            }

            $modes = match ($this->option('modo')) {
                'normal' => [VencimientoSimulationService::MODE_NORMAL],
                'ia' => [VencimientoSimulationService::MODE_IA],
                'ambos' => [VencimientoSimulationService::MODE_NORMAL, VencimientoSimulationService::MODE_IA],
                default => null,
            };

            if ($modes === null) {
                $this->error('--modo debe ser normal, ia o ambos.');

                return self::INVALID;
            }

            $out = $simulation->simulate($name, $modes, (int) $this->option('dias'));
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line(sprintf('Comité: %s', $out['committee']->name));
        $this->line(sprintf(
            'Resolución %s · vigencia hasta %s',
            $out['resolution']->document,
            $out['resolution']->date_end->format('d/m/Y')
        ));
        $this->line(sprintf('Fecha simulada: %s', $out['today']->format('d/m/Y')));
        $this->line(sprintf(
            'Estado: %s → %s  (datos reales sin modificar)',
            $this->label($out['state_before']),
            $this->label($out['state_after'])
        ));
        $this->newLine();

        foreach ($out['results'] as $result) {
            $notification = $result['notification'];
            $isAi = $result['mode'] === VencimientoSimulationService::MODE_IA;

            $this->info(sprintf('[%s] aviso #%d: %s', $isAi ? 'IA' : 'NORMAL', $notification->id, $notification->title));
            $this->line('  '.$notification->description);

            if ($isAi) {
                $ia = $notification->metadata['ia'] ?? null;
                if ($ia) {
                    $this->line('  Resumen IA: '.$ia['resumen']);
                    $this->line('  Acción: '.$ia['accion_sugerida'].' (prioridad '.$ia['prioridad'].')');
                } else {
                    $this->warn('  La IA no respondió: se emitió con el texto base. Revisa AI_PROVIDER, la clave y storage/logs.');
                }
            }
        }

        $this->newLine();
        $this->line('Revisa las campanas de notificaciones del panel. Para borrarlas: php artisan alertas:simular-comite "'.$name.'" --limpiar');

        return self::SUCCESS;
    }

    private function label(string $abbreviation): string
    {
        return $abbreviation === State::CURRENT ? 'Activo' : ($abbreviation === State::INACTIVE ? 'Inactivo' : $abbreviation);
    }
}
