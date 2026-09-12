<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssistantAiService
{
    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    public function generate(array $messages, string $systemPrompt): ?string
    {
        $provider = strtolower(trim((string) config('services.ai.provider', 'groq')));

        if ($provider === 'none' || $provider === '') {
            return null;
        }

        try {
            return match ($provider) {
                'google' => $this->generateWithGoogle($messages, $systemPrompt),
                'groq' => $this->generateWithGroq($messages, $systemPrompt),
                default => $this->unsupportedProvider($provider),
            };
        } catch (ConnectionException $exception) {
            Log::warning('No se pudo conectar con el proveedor del asistente.', [
                'provider' => $provider,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    private function generateWithGroq(array $messages, string $systemPrompt): ?string
    {
        $apiKey = trim((string) config('services.groq.key'));

        if ($apiKey === '') {
            return null;
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($apiKey)
            ->connectTimeout(5)
            ->timeout(25)
            ->post((string) config('services.groq.url'), [
                'model' => config('services.groq.model'),
                'messages' => array_merge([
                    ['role' => 'system', 'content' => $systemPrompt],
                ], $messages),
                'temperature' => 0.2,
                'max_completion_tokens' => 600,
            ]);

        if ($this->requestFailed($response, 'groq')) {
            return null;
        }

        return trim((string) $response->json('choices.0.message.content')) ?: null;
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    private function generateWithGoogle(array $messages, string $systemPrompt): ?string
    {
        $apiKey = trim((string) config('services.google_ai.key'));

        if ($apiKey === '') {
            return null;
        }

        $model = trim((string) config('services.google_ai.model'));
        $baseUrl = rtrim((string) config('services.google_ai.url'), '/');
        $url = $baseUrl.'/'.rawurlencode($model).':generateContent';

        $contents = array_map(static fn (array $message) => [
            'role' => $message['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $message['content']]],
        ], $messages);

        $response = Http::acceptJson()
            ->asJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->connectTimeout(5)
            ->timeout(25)
            ->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 600,
                ],
            ]);

        if ($this->requestFailed($response, 'google')) {
            return null;
        }

        $parts = $response->json('candidates.0.content.parts', []);

        if (! is_array($parts)) {
            return null;
        }

        $answer = collect($parts)
            ->pluck('text')
            ->filter(static fn ($text) => is_string($text))
            ->implode('');

        return trim($answer) ?: null;
    }

    private function requestFailed(Response $response, string $provider): bool
    {
        if (! $response->failed()) {
            return false;
        }

        Log::warning('El proveedor del asistente rechazó la solicitud.', [
            'provider' => $provider,
            'status' => $response->status(),
        ]);

        return true;
    }

    private function unsupportedProvider(string $provider): ?string
    {
        Log::warning('Proveedor de IA no compatible.', ['provider' => $provider]);

        return null;
    }
}
