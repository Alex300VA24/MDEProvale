<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssistantAiService;
use App\Services\AssistantDataService;
use App\Services\AssistantGuidanceService;
use App\Services\AssistantPromptService;
use App\Services\AssistantReportService;
use App\Support\AssistantAccess;
use App\Services\AssistantSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssistantChatController extends Controller
{
    public function __invoke(
        Request $request,
        AssistantAiService $ai,
        AssistantGuidanceService $guidance,
        AssistantDataService $datos,
        AssistantReportService $reportes,
        AssistantSettingsService $config,
        AssistantPromptService $prompts
    ): JsonResponse {
        $validated = $request->validate([
            'mensajes' => ['required', 'array', 'min:1', 'max:20'],
            'mensajes.*.role' => ['required', 'string', 'in:user,assistant'],
            'mensajes.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $messages = collect($validated['mensajes'])
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => trim($message['content']),
            ])
            ->values()
            ->all();

        if (end($messages)['role'] !== 'user') {
            throw ValidationException::withMessages([
                'mensajes' => 'El último mensaje debe pertenecer al usuario.',
            ]);
        }

        $key = 'asistente-chat:'.$request->user()->id;
        $maxConsultas = $config->maxConsultas();
        $ventanaSegundos = $config->ventanaSegundos();

        if (RateLimiter::tooManyAttempts($key, $maxConsultas)) {
            return $this->limiteExcedido($key, $maxConsultas);
        }

        RateLimiter::hit($key, $ventanaSegundos);

        $pregunta = end($messages)['content'];

        // Todo lo que responde el asistente respeta los módulos del rol del usuario.
        $access = AssistantAccess::for($request->user());

        // 1) Solicitud explícita de reporte.
        $reporte = $reportes->detect($pregunta, $access);
        if ($reporte) {
            return $this->responder(
                $key,
                $maxConsultas,
                $reporte['respuesta'],
                $reporte['accion'] ?? null,
                $reporte['sugerencias'] ?? null
            );
        }

        // 2) Consulta puntual de datos (respuesta determinista, sin modelo).
        $dato = $datos->resolveConversation($messages, $access);
        if ($dato !== null) {
            return $this->responder($key, $maxConsultas, $dato);
        }

        // 2.5) Menciona un reporte pero AssistantReportService no pudo construirlo
        // (entidad o periodo ambiguos). Respuesta determinista, nunca el modelo:
        // así no inventa módulos ni menús de exportación inexistentes.
        $normalizada = Str::lower(Str::ascii($pregunta));
        if (preg_match('/\b(reporte|reportes|informe|informes|exporta|exportar|exportame)\b/', $normalizada)) {
            $clarification = $guidance->clarification($pregunta);

            return $this->responder(
                $key,
                $maxConsultas,
                $clarification['respuesta'],
                null,
                $clarification['sugerencias']
            );
        }

        // 3) Guía de navegación local y, si hay clave, redacción con el modelo.
        $localAnswer = $guidance->answer($pregunta, $access);
        $iaActiva = $ai->isConfigured();
        $answer = $iaActiva
            ? $this->sanear($ai->generate($messages, $prompts->build($access)) ?? '')
            : '';

        if ($answer === '') {
            if ($localAnswer !== null) {
                return $this->responder($key, $maxConsultas, $localAnswer);
            }

            if (! $iaActiva) {
                return $this->responder($key, $maxConsultas, AssistantAiService::DISABLED_MESSAGE);
            }

            $clarification = $guidance->clarification($pregunta);

            return $this->responder(
                $key,
                $maxConsultas,
                $clarification['respuesta'],
                null,
                $clarification['sugerencias']
            );
        }

        return $this->responder($key, $maxConsultas, $answer);
    }

    private function responder(
        string $key,
        int $maxConsultas,
        string $respuesta,
        ?array $accion = null,
        ?array $sugerencias = null
    ): JsonResponse
    {
        $payload = [
            'respuesta' => $respuesta,
            'limite' => $this->estadoLimite($key, $maxConsultas),
        ];

        if ($accion) {
            $payload['accion'] = $accion;
        }

        if ($sugerencias) {
            $payload['sugerencias'] = $sugerencias;
        }

        return response()->json($payload);
    }

    private function limiteExcedido(string $key, int $maxConsultas): JsonResponse
    {
        $segundos = RateLimiter::availableIn($key);
        $reinicia = now()->addSeconds($segundos)->toIso8601String();

        return response()->json([
            'mensaje' => 'Alcanzaste el límite de '.$maxConsultas.' consultas por ahora. '
                .'Podrás volver a preguntar '.$this->humanizar($segundos).'.',
            'reinicia_en' => $reinicia,
            'limite' => ['restantes' => 0, 'total' => $maxConsultas, 'reinicia_en' => $reinicia],
        ], 429);
    }

    /**
     * @return array{restantes:int,total:int,reinicia_en:string}
     */
    private function estadoLimite(string $key, int $maxConsultas): array
    {
        $restantes = max(0, RateLimiter::remaining($key, $maxConsultas));

        return [
            'restantes' => $restantes,
            'total' => $maxConsultas,
            'reinicia_en' => now()->addSeconds(RateLimiter::availableIn($key))->toIso8601String(),
        ];
    }

    private function humanizar(int $segundos): string
    {
        $minutos = (int) ceil(max(1, $segundos) / 60);

        if ($minutos < 60) {
            return "en {$minutos} min";
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return $resto ? "en {$horas} h {$resto} min" : "en {$horas} h";
    }

    /**
     * Quita restos de Markdown que el modelo pueda devolver, conservando las
     * tablas con barras verticales y las viñetas con guion.
     */
    private function sanear(string $texto): string
    {
        $texto = preg_replace('/\*\*(.+?)\*\*/s', '$1', $texto);
        $texto = preg_replace('/(?<!\*)\*(?!\*)\s*/', '', $texto);
        $texto = preg_replace('/`{1,3}([^`]*)`{1,3}/', '$1', $texto);
        $texto = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $texto);
        $texto = preg_replace('/^\s{0,3}>\s?/m', '', $texto);
        $texto = preg_replace('/_{2,}/', '', $texto);
        $texto = str_replace('—', '-', $texto);

        return trim($texto);
    }
}
