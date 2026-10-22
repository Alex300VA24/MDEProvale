<?php

namespace App\Services\Alertas;

use App\Models\BeneficiaryHistory;
use App\Models\Resolution;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\VencimientoRepositoryInterface;
use App\Services\AssistantAiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Avisos de vencimiento. La detección y los textos base son deterministas
 * (reglas sobre la BD); Gemini solo añade priorización y una acción sugerida.
 * Si la IA está apagada o falla, el aviso se emite igual sin ese complemento.
 *
 * A la IA nunca se envían nombres ni documentos de personas: solo conteos,
 * edades, fechas y el nombre del comité.
 */
class VencimientoAlertService
{
    public const TYPE_EDAD = 'alerta_beneficiarios';
    public const TYPE_RESOLUCION = 'alerta_resolucion';

    public function __construct(
        private AssistantAiService $ai,
        private VencimientoRepositoryInterface $alerts,
        private NotificationRepositoryInterface $notifications,
    ) {
    }

    /**
     * @return array{edad:int, beneficios:int, resoluciones:int, con_ia:int}
     */
    public function scanAndNotify(?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();

        $result = ['edad' => 0, 'beneficios' => 0, 'resoluciones' => 0, 'con_ia' => 0];

        foreach ($this->detectAgeAlerts($today) as $alert) {
            if ($this->emit($alert, self::TYPE_EDAD, $result)) {
                $result['edad']++;
            }
        }

        foreach ($this->detectBenefitEndAlerts($today) as $alert) {
            if ($this->emit($alert, self::TYPE_EDAD, $result)) {
                $result['beneficios']++;
            }
        }

        foreach ($this->detectResolutionAlerts($today) as $alert) {
            if ($this->emit($alert, self::TYPE_RESOLUCION, $result)) {
                $result['resoluciones']++;
            }
        }

        return $result;
    }

    /**
     * Avisos previos para conceptos GES/LAC con fecha de término calculada.
     */
    public function detectBenefitEndAlerts(Carbon $today): Collection
    {
        $thresholds = collect(config('alertas.beneficios.umbrales_dias', [60, 30, 7]))
            ->map(fn ($days) => (int) $days)->filter(fn ($days) => $days > 0)->sort()->values();

        if ($thresholds->isEmpty()) {
            return collect();
        }

        return $this->alerts->benefitsEndingWithin($today, $thresholds->max())
            ->map(function (BeneficiaryHistory $history) use ($today, $thresholds) {
                $days = (int) $today->diffInDays(Carbon::parse($history->date_end)->startOfDay(), false);
                $stage = (string) $thresholds->first(fn ($threshold) => $days <= $threshold);
                $name = $history->beneficiary?->name ?? 'Beneficiario';
                $benefit = $history->typeBenefit?->abbreviation ?? 'BENEFICIO';

                return [
                    'dedupe_key' => "benefit-end:{$history->id}:{$history->date_end}:{$stage}",
                    'title' => "{$benefit}: beneficio por finalizar",
                    'description' => "El beneficio de {$name} finaliza en {$days} día(s).",
                    'metadata' => [
                        'urgencia' => $this->urgency($days),
                        'beneficiary_history_id' => $history->id,
                        'beneficiary_id' => $history->beneficiary_id,
                        'association_id' => $history->beneficiary?->partner?->association_id,
                        'tipo_beneficio' => $benefit,
                        'fecha_fin' => (string) $history->date_end,
                        'dias_restantes' => $days,
                    ],
                    'ai_facts' => [
                        'tipo' => 'beneficio_por_finalizar',
                        'concepto' => $benefit,
                        'dias_restantes' => $days,
                    ],
                ];
            });
    }

    /**
     * Un aviso por comité con beneficiarios que superan (o están por superar)
     * el límite de edad de su tipo de beneficio.
     *
     * @return Collection<int, array>
     */
    public function detectAgeAlerts(Carbon $today): Collection
    {
        $ahead = (int) config('alertas.edad.dias_anticipacion', 60);
        $grace = (int) config('alertas.edad.dias_gracia', 30);

        $histories = collect();

        // Salta años: el límite se cruza al cumplir (max_age + 1) años.
        $maxAges = $this->alerts->maxAgeByBenefitType();

        foreach ($maxAges as $typeId => $maxAge) {
            $years = (int) $maxAge + 1;
            $bornFrom = $today->copy()->subYears($years)->subDays($grace)->toDateString();
            $bornTo = $today->copy()->subYears($years)->addDays($ahead)->toDateString();

            $found = $this->alerts->benefitsBornBetween((int) $typeId, $today, $bornFrom, $bornTo);

            $histories = $histories->concat($found);
        }

        return $histories
            ->filter(fn ($h) => $h->beneficiary?->person?->birthdate && $h->beneficiary->partner?->association)
            ->groupBy(fn ($h) => $h->beneficiary->partner->association->id)
            ->map(function (Collection $group) use ($today) {
                $association = $group->first()->beneficiary->partner->association;

                $items = $group->map(function ($h) use ($today) {
                    $birth = Carbon::parse($h->beneficiary->person->birthdate);
                    $limitDate = $birth->copy()->addYears((int) $h->typeBenefit->max_age + 1);

                    return [
                        'beneficiary_id' => $h->beneficiary_id,
                        'nombre' => $h->beneficiary->name,
                        'tipo_beneficio' => $h->typeBenefit->title,
                        'edad_limite' => (int) $h->typeBenefit->max_age,
                        'fecha_limite' => $limitDate->toDateString(),
                        'dias' => (int) $today->diffInDays($limitDate, false),
                    ];
                })->sortBy('dias')->values();

                $ids = $items->pluck('beneficiary_id')->sort()->values()->all();
                $vencidos = $items->where('dias', '<', 0)->count();
                $max = (int) config('alertas.max_items_por_aviso', 25);

                $title = sprintf(
                    '%s: %d beneficiario(s) por superar el límite de edad',
                    $association->name,
                    $items->count()
                );

                $minDias = (int) $items->min('dias');
                $summary = $vencidos > 0
                    ? sprintf('%d ya superaron el límite. Revisa el padrón del comité.', $vencidos)
                    : sprintf('El primero cumple el límite en %d día(s). Revisa el padrón del comité.', $minDias);

                return [
                    'dedupe_key' => 'edad:'.$association->id.':'.md5(implode(',', $ids)),
                    'title' => $title,
                    'description' => $summary,
                    'metadata' => [
                        'urgencia' => $this->urgency($minDias),
                        'association_id' => $association->id,
                        'comite' => $association->name,
                        'beneficiarios' => $items->take($max)->all(),
                        'total' => $items->count(),
                        'ya_superaron' => $vencidos,
                    ],
                    // Sin datos personales: es lo único que ve la IA.
                    'ai_facts' => [
                        'tipo' => 'beneficiarios_por_superar_limite_de_edad',
                        'comite' => $association->name,
                        'total' => $items->count(),
                        'ya_superaron_limite' => $vencidos,
                        'proximo_vencimiento_dias' => $items->min('dias'),
                        'por_tipo_beneficio' => $items->groupBy('tipo_beneficio')
                            ->map(fn ($g) => $g->count())->all(),
                    ],
                ];
            })
            ->values();
    }

    /**
     * Un aviso por resolución (y umbral cruzado) cuya vigencia está por
     * terminar. Solo cuenta comités para los que esa es su resolución actual.
     *
     * @return Collection<int, array>
     */
    public function detectResolutionAlerts(Carbon $today): Collection
    {
        $thresholds = collect(config('alertas.resoluciones.umbrales_dias', [60, 30, 7]))
            ->map(fn ($d) => (int) $d)->filter(fn ($d) => $d > 0)->sort()->values();

        if ($thresholds->isEmpty()) {
            return collect();
        }

        $grace = (int) config('alertas.resoluciones.dias_gracia', 30);

        return $this->alerts
            ->resolutionsEndingBetween($today->copy()->subDays($grace), $today->copy()->addDays($thresholds->max()))
            ->filter(fn (Resolution $r) => $r->primaryAssociations->isNotEmpty())
            ->map(function (Resolution $resolution) use ($today, $thresholds) {
                $days = (int) $today->diffInDays($resolution->date_end->copy()->startOfDay(), false);
                $stage = $days < 0 ? 'vencida' : (string) $thresholds->first(fn ($t) => $days <= $t);
                $committees = $resolution->primaryAssociations;
                $max = (int) config('alertas.max_items_por_aviso', 25);

                $title = $days < 0
                    ? sprintf('Resolución %s venció hace %d día(s)', $resolution->document, abs($days))
                    : sprintf('Resolución %s vence en %d día(s)', $resolution->document, $days);

                $summary = sprintf(
                    'Vigencia hasta el %s. Afecta a %d comité(s).',
                    $resolution->date_end->format('d/m/Y'),
                    $committees->count()
                );

                return [
                    'dedupe_key' => 'resolucion:'.$resolution->id.':'.$resolution->date_end->toDateString().':'.$stage,
                    'title' => $title,
                    'description' => $summary,
                    'metadata' => [
                        'urgencia' => $this->urgency($days),
                        'resolution_id' => $resolution->id,
                        'documento' => $resolution->document,
                        'fecha_fin' => $resolution->date_end->toDateString(),
                        'dias_restantes' => $days,
                        'comites' => $committees->take($max)->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all(),
                        'total_comites' => $committees->count(),
                    ],
                    'ai_facts' => [
                        'tipo' => 'resolucion_de_comites_por_vencer',
                        'dias_restantes' => $days,
                        'fecha_fin' => $resolution->date_end->toDateString(),
                        'comites_alcanzados' => $committees->count(),
                        'ya_vencida' => $days < 0,
                    ],
                ];
            })
            ->values();
    }

    private function emit(array $alert, string $type, array &$result): bool
    {
        if ($this->notifications->existsWithDedupeKey($type, $alert['dedupe_key'])) {
            return false;
        }

        $description = $alert['description'];
        $metadata = $alert['metadata'] + ['dedupe_key' => $alert['dedupe_key']];

        $insight = $this->insight($alert['ai_facts']);
        if ($insight) {
            $metadata['ia'] = $insight;
            $result['con_ia']++;
        }

        $this->notifications->create([
            'type' => $type,
            'dedupe_key' => $alert['dedupe_key'],
            'title' => $alert['title'],
            'description' => $description,
            'status' => 'informativo',
            'requested_at' => now(),
            'is_seen' => false,
            'metadata' => $metadata,
        ]);

        return true;
    }

    /** Urgencia determinista por días restantes: no depende de la IA. */
    private function urgency(int $days): string
    {
        return match (true) {
            $days <= 7 => 'urgente',
            $days <= 30 => 'proximo',
            default => 'aviso',
        };
    }

    /**
     * Complemento opcional con IA. Cualquier fallo devuelve null y el aviso
     * sigue su curso con el texto base.
     *
     * @return array{resumen:string, accion_sugerida:string, prioridad:string}|null
     */
    private function insight(array $facts): ?array
    {
        if (! config('alertas.ai_enabled') || $this->ai->provider() === 'none') {
            return null;
        }

        try {
            $out = $this->ai->generateStructured(
                ['hechos' => $facts],
                [
                    'type' => 'OBJECT',
                    'properties' => [
                        'resumen' => ['type' => 'STRING'],
                        'accion_sugerida' => ['type' => 'STRING'],
                        'prioridad' => ['type' => 'STRING', 'enum' => ['alta', 'media', 'baja']],
                    ],
                    'required' => ['resumen', 'accion_sugerida', 'prioridad'],
                ],
                $this->systemPrompt(),
            );
        } catch (Throwable $e) {
            Log::warning('Aviso de vencimiento sin complemento de IA.', ['error' => $e->getMessage()]);

            return null;
        }

        if (! is_array($out)) {
            return null;
        }

        $resumen = trim((string) ($out['resumen'] ?? ''));
        $accion = trim((string) ($out['accion_sugerida'] ?? ''));
        $prioridad = (string) ($out['prioridad'] ?? '');

        if ($resumen === '' || $accion === '' || ! in_array($prioridad, ['alta', 'media', 'baja'], true)) {
            return null;
        }

        return [
            'resumen' => mb_substr($resumen, 0, 400),
            'accion_sugerida' => mb_substr($accion, 0, 400),
            'prioridad' => $prioridad,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Eres un asistente del Programa del Vaso de Leche de una municipalidad peruana.
Recibes hechos ya calculados por el sistema sobre un vencimiento próximo (límite de edad de beneficiarios o fin de vigencia de una resolución de comités).

Tarea: redacta en español, para el personal administrativo, un resumen de una o dos frases y una acción sugerida concreta, y asigna prioridad (alta si ya venció o faltan 7 días o menos; media si faltan hasta 30; baja en otro caso).

Reglas:
1. Usa solo los hechos recibidos. No inventes cifras, fechas, nombres ni normas.
2. No cites artículos de ley ni procedimientos que no estén en los hechos.
3. La acción debe ser operativa (por ejemplo: revisar el padrón del comité, gestionar la renovación de la resolución, reclasificar o dar de baja).
4. Devuelve solo JSON válido según el esquema.
PROMPT;
    }
}
