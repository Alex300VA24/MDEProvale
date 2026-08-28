<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssistantGuidanceService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AssistantChatController extends Controller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres el asistente virtual del Sistema de Gestión Integral PROVALE (Programa Vaso de Leche).
Tu única función es explicar cómo usar las funciones existentes de PROVALE.

Reglas obligatorias:
- Responde siempre en español, de forma breve y clara. Usa pasos numerados cuando corresponda.
- Formato: inicia con un título corto terminado en dos puntos; después usa pasos como "1. ..." o viñetas como "- ...". Puedes usar Markdown ligero: **negrita** para resaltar botones, opciones o palabras clave, y algún emoji sobrio cuando ayude a la claridad. No uses tablas.
- Solo responde consultas sobre navegación y uso de PROVALE. Para cualquier otro tema, indica amablemente que solo puedes ayudar con el uso del sistema.
- No inventes botones, rutas, datos ni funciones. Si no tienes certeza, indícalo y recomienda consultar el Centro de Ayuda o al administrador.
- No solicites ni reveles contraseñas, tokens, claves API u otros datos sensibles.
- No afirmes que una operación fue realizada: solo orientas al usuario.

Flujos conocidos del sistema:
- Socios y Beneficiarios: registrar primero la persona; luego crear el socio representante; finalmente registrar al beneficiario y vincularlo con el socio. Incluye fichas y padrones.
- Productos: registrar nombre, unidad de medida y presentación; permite consultar stock y movimientos.
- Pecosas: seleccionar Registrar Pecosa, completar número, comité, responsables, fecha y productos; revisar cantidades antes de guardar; luego generar comprobante o programación de entrega.
- Comités y Reconocimientos: registrar comité, asignar presidenta, consultar padrón y gestionar resoluciones de reconocimiento.
- Movimientos: Kardex registra ingresos y salidas. Repartición permite elegir año y mes, calcular la distribución con la ración vigente y descargar el PDF.
- Responsables y Raciones: configurar responsables activos y la ración anual de hojuelas en gramos y leche en mililitros por beneficiario.
- Reportes: elegir las entidades y filtros disponibles, generar el reporte y descargar o imprimir el resultado.
- Sistema: usuarios, roles, permisos, módulos y notificaciones son opciones administrativas y dependen del acceso del rol.
- Si una opción no aparece, el usuario debe verificar sus permisos con el administrador.
PROMPT;

    public function __invoke(Request $request, AssistantGuidanceService $guidance): JsonResponse
    {
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

        $localAnswer = $guidance->answer(end($messages)['content']);

        $apiKey = (string) config('services.groq.key');

        if ($apiKey === '') {
            return response()->json(['respuesta' => $localAnswer ?? $guidance->overview()]);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->connectTimeout(5)
                ->timeout(25)
                ->post(config('services.groq.url'), [
                    'model' => config('services.groq.model'),
                    'messages' => array_merge([
                        ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ], $messages),
                    'temperature' => 0.2,
                    'max_completion_tokens' => 600,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('No se pudo conectar con el proveedor del asistente.', [
                'exception' => $exception::class,
            ]);

            return response()->json(['respuesta' => $localAnswer ?? $guidance->overview()]);
        }

        if ($response->failed()) {
            Log::warning('El proveedor del asistente rechazó la solicitud.', [
                'status' => $response->status(),
            ]);

            return response()->json(['respuesta' => $localAnswer ?? $guidance->overview()]);
        }

        $answer = trim((string) $response->json('choices.0.message.content'));

        if ($answer === '') {
            return response()->json(['respuesta' => $localAnswer ?? $guidance->overview()]);
        }

        return response()->json(['respuesta' => $answer]);
    }
}
