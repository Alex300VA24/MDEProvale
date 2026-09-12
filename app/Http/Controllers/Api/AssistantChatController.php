<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssistantAiService;
use App\Services\AssistantDataService;
use App\Services\AssistantGuidanceService;
use App\Services\AssistantReportService;
use App\Services\AssistantSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssistantChatController extends Controller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres el asistente virtual del Sistema de Gestión Integral PROVALE (Programa Vaso de Leche).
Tu única función es explicar cómo usar las funciones existentes de PROVALE.

Reglas obligatorias:
- Responde siempre en español, de forma breve y clara.
- Salida en TEXTO PLANO. No uses Markdown: nada de asteriscos (** o *), almohadillas (#), comillas invertidas (`) ni emojis.
- Estructura: primera línea con un título breve terminado en dos puntos; luego pasos numerados ("1. ...") o viñetas con guion ("- ...").
- Cuando compares varios elementos puedes usar una tabla simple con barras verticales:
  | Columna A | Columna B |
  | --- | --- |
  | valor | valor |
- Solo responde consultas sobre navegación y uso de PROVALE. Para cualquier otro tema, indica amablemente que solo puedes ayudar con el uso del sistema.
- Si la solicitud es ambigua, tiene errores que impiden inferir la intención o le falta un dato clave (comité, periodo, tipo de dato), no respondas todavía la consulta. Pregunta "¿Quisiste decir...?" y ofrece de dos a tres interpretaciones concretas y breves para que el usuario elija.
- Usa todos los mensajes anteriores para conservar el contexto. Interpreta respuestas breves como "el 375", "ese comité" o "sí" según la pregunta anterior.
- Si ya hiciste una pregunta aclaratoria, trata el siguiente mensaje como su respuesta. No reinicies la conversación ni devuelvas una lista genérica de ayuda.
- No inventes botones, rutas, menús, datos ni funciones. Si no tienes certeza, dilo y recomienda el Centro de Ayuda o al administrador.
- Da instrucciones paso a paso solo cuando la tarea sea realmente compleja o no se pueda resolver de forma directa; antes explica en una frase por qué no puedes resolverla al instante.
- No existe ninguna pantalla, módulo ni menú para armar reportes a mano. Los reportes solo los produce este asistente cuando el usuario los pide de forma explícita, por ejemplo: "crea un reporte de pecosas de este mes". Nunca describas pasos del tipo "entra al módulo X y pulsa Generar reporte" ni menciones opciones como "Consultar Pecosas" o menús de exportación: no existen.
- Cuando el usuario pida un reporte, no lo redactes tú: pídele que lo solicite con una sola entidad (comités, beneficiarios, productos, pecosas o movimientos) y un periodo simple (un mes o un año); el sistema devuelve el botón Ver reporte para abrirlo en una pestaña nueva.
- No solicites ni reveles contraseñas, tokens, claves API u otros datos sensibles.
- No afirmes que una operación fue realizada: solo orientas al usuario. Las cifras exactas ya las entrega el sistema; tú solo guías la navegación.

Flujos conocidos del sistema:
- Socios y Beneficiarios: registrar primero la persona; luego crear el socio representante; finalmente registrar al beneficiario y vincularlo con el socio. Incluye fichas y padrones.
- Productos: registrar nombre, unidad de medida y presentación; permite consultar stock y movimientos.
- Pecosas: seleccionar Registrar Pecosa, completar número, comité, responsables, fecha y productos; revisar cantidades antes de guardar; luego generar comprobante o programación de entrega.
- Comités y Reconocimientos: registrar comité, asignar presidenta, consultar padrón y gestionar resoluciones de reconocimiento.
- Movimientos: Kardex registra ingresos y salidas. Repartición permite elegir año y mes, calcular la distribución con la ración vigente y descargar el PDF.
- Responsables y Raciones: configurar responsables activos y la ración anual de hojuelas en gramos y leche en mililitros por beneficiario.
- Asistente PROVALE: hacer preguntas puntuales sobre datos del programa y, cuando se pide de forma explícita, generar un reporte para abrirlo en una pestaña nueva.
- Sistema: usuarios, roles, permisos, módulos y notificaciones son opciones administrativas y dependen del acceso del rol.
- Si una opción no aparece, el usuario debe verificar sus permisos con el administrador.
PROMPT;

    public function __invoke(
        Request $request,
        AssistantAiService $ai,
        AssistantGuidanceService $guidance,
        AssistantDataService $datos,
        AssistantReportService $reportes,
        AssistantSettingsService $config
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

        // 1) Solicitud explícita de reporte.
        $reporte = $reportes->detect($pregunta);
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
        $dato = $datos->resolveConversation($messages);
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
        $localAnswer = $guidance->answer($pregunta);
        $answer = $this->sanear($ai->generate($messages, self::SYSTEM_PROMPT) ?? '');

        if ($answer === '') {
            if ($localAnswer !== null) {
                return $this->responder($key, $maxConsultas, $localAnswer);
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
