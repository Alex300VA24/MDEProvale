<?php

namespace Tests\Unit;

use App\Exceptions\AiProviderException;
use App\Services\AssistantAiService;
use App\Services\Pvl\PvlAiReportService;
use App\Services\Pvl\PvlReportDataMapper;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantAiServiceStructuredTest extends TestCase
{
    public function test_gemini_chat_uses_the_configured_output_budget_for_complete_answers(): void
    {
        config()->set('services.ai.provider', 'google');
        config()->set('services.ai.chat_max_tokens', 8192);
        config()->set('services.google_ai.key', 'google-key');
        config()->set('services.google_ai.model', 'gemini-test');
        config()->set('services.google_ai.url', 'https://gemini.test/models');
        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => "## Resumen\n\nRespuesta completa."]]],
                ]],
            ]),
        ]);

        $answer = (new AssistantAiService())->generate(
            [['role' => 'user', 'content' => '¿De qué trata el acuerdo?']],
            'Responde con las fuentes disponibles.',
        );

        $this->assertSame("## Resumen\n\nRespuesta completa.", $answer);
        Http::assertSent(fn ($request): bool => $request['generationConfig']['maxOutputTokens'] === 8192
            && $request['generationConfig']['responseMimeType'] === 'text/plain');
    }

    public function test_structured_generation_preserves_gemini_rate_limit_as_429(): void
    {
        config()->set('services.ai.provider', 'google');
        config()->set('services.ai.structured_retries', 1);
        config()->set('services.google_ai.key', 'test-key');
        config()->set('services.google_ai.model', 'gemini-test');
        config()->set('services.google_ai.url', 'https://gemini.test/models');
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'quota exceeded']], 429, ['Retry-After' => '60']),
        ]);

        try {
            (new AssistantAiService())->generateStructured(
                ['periodo' => '2026-06'],
                ['type' => 'OBJECT', 'properties' => []],
                'Devuelve JSON.',
            );
            $this->fail('Se esperaba AiProviderException.');
        } catch (AiProviderException $exception) {
            $this->assertSame(429, $exception->httpStatus());
            $this->assertSame('RATE_LIMIT', $exception->reason());
            $this->assertStringContainsString('límite de uso', $exception->getMessage());
        }
    }

    public function test_structured_generation_uses_groq_when_it_is_the_selected_provider(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.groq.key', 'groq-key');
        config()->set('services.groq.model', 'openai/gpt-oss-120b');
        config()->set('services.groq.url', 'https://api.groq.test/openai/v1/chat/completions');
        config()->set('services.groq.structured_strict', true);
        Http::fake([
            'api.groq.test/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => '{"estado":"LISTO","detalle":null}'],
                ]],
            ]),
        ]);

        $result = (new AssistantAiService())->generateStructured(
            ['periodo' => '2026-06'],
            [
                'type' => 'OBJECT',
                'properties' => [
                    'estado' => ['type' => 'STRING'],
                    'detalle' => ['type' => 'STRING', 'nullable' => true],
                ],
                'required' => ['estado'],
            ],
            'Devuelve JSON.',
        );

        $this->assertSame(['estado' => 'LISTO', 'detalle' => null], $result);
        Http::assertSent(function ($request): bool {
            $schema = $request['response_format']['json_schema']['schema'];

            return $request->url() === 'https://api.groq.test/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer groq-key')
                && $request['model'] === 'openai/gpt-oss-120b'
                && $request['response_format']['type'] === 'json_schema'
                && $request['response_format']['json_schema']['strict'] === true
                && $schema['type'] === 'object'
                && $schema['required'] === ['estado', 'detalle']
                && $schema['additionalProperties'] === false
                && $schema['properties']['detalle']['type'] === ['string', 'null'];
        });
    }

    public function test_groq_rate_limit_is_reported_without_falling_back_to_google(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.ai.structured_retries', 1);
        config()->set('services.groq.key', 'groq-key');
        config()->set('services.groq.url', 'https://api.groq.test/openai/v1/chat/completions');
        Http::fake([
            'api.groq.test/*' => Http::response(['error' => ['message' => 'rate limit']], 429),
        ]);

        try {
            (new AssistantAiService())->generateStructured(
                ['periodo' => '2026-06'],
                ['type' => 'OBJECT', 'properties' => []],
                'Devuelve JSON.',
            );
            $this->fail('Se esperaba AiProviderException.');
        } catch (AiProviderException $exception) {
            $this->assertSame(429, $exception->httpStatus());
            $this->assertSame('RATE_LIMIT', $exception->reason());
            $this->assertStringContainsString('Groq', $exception->getMessage());
        }

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.test'));
    }

    public function test_structured_generation_retries_transient_failures_and_then_succeeds(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.ai.structured_retries', 3);
        config()->set('services.ai.structured_retry_base_ms', 0);
        config()->set('services.groq.key', 'groq-key');
        config()->set('services.groq.url', 'https://api.groq.test/openai/v1/chat/completions');
        Http::fake([
            'api.groq.test/*' => Http::sequence()
                ->push(['error' => ['code' => 'json_validate_failed', 'message' => 'fallo transitorio']], 400)
                ->push(['error' => ['code' => 'structured_generation_failed', 'message' => 'fallo transitorio']], 400)
                ->push(['choices' => [[
                    'message' => ['content' => '{"estado":"LISTO","detalle":null}'],
                ]]])->whenEmpty(Http::response([], 500)),
        ]);

        $result = (new AssistantAiService())->generateStructured(
            ['periodo' => '2026-06'],
            ['type' => 'OBJECT', 'properties' => ['estado' => ['type' => 'STRING']]],
            'Devuelve JSON.',
        );

        $this->assertSame(['estado' => 'LISTO', 'detalle' => null], $result);
        Http::assertSentCount(3);
    }

    public function test_structured_generation_stops_retrying_after_a_transient_400_sequence(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.ai.structured_retries', 3);
        config()->set('services.ai.structured_retry_base_ms', 0);
        config()->set('services.groq.key', 'groq-key');
        config()->set('services.groq.url', 'https://api.groq.test/openai/v1/chat/completions');
        Http::fake([
            'api.groq.test/*' => Http::response(['error' => ['code' => 'json_validate_failed', 'message' => 'fallo transitorio']], 400),
        ]);

        try {
            (new AssistantAiService())->generateStructured(
                ['periodo' => '2026-06'],
                ['type' => 'OBJECT', 'properties' => []],
                'Devuelve JSON.',
            );
            $this->fail('Se esperaba AiProviderException.');
        } catch (AiProviderException $exception) {
            $this->assertSame('TRANSIENT', $exception->reason());
            $this->assertTrue($exception->isTransient());
            $this->assertSame('json_validate_failed', $exception->providerCode());
            $this->assertStringContainsString('fallo transitorio', $exception->getMessage());
        }

        Http::assertSentCount(3);
    }

    public function test_structured_generation_marks_a_permanent_bad_request_as_request_rejected(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.ai.structured_retries', 1);
        config()->set('services.groq.key', 'groq-key');
        config()->set('services.groq.url', 'https://api.groq.test/openai/v1/chat/completions');
        Http::fake([
            'api.groq.test/*' => Http::response(['error' => ['code' => 'invalid_request_error', 'message' => 'schema inválido']], 400),
        ]);

        try {
            (new AssistantAiService())->generateStructured(
                ['periodo' => '2026-06'],
                ['type' => 'OBJECT', 'properties' => []],
                'Devuelve JSON.',
            );
            $this->fail('Se esperaba AiProviderException.');
        } catch (AiProviderException $exception) {
            $this->assertSame(502, $exception->httpStatus());
            $this->assertSame('REQUEST_REJECTED', $exception->reason());
            $this->assertFalse($exception->isTransient());
            $this->assertStringContainsString('schema inválido', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_groq_uses_lexical_rag_without_calling_google_embeddings(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.google_ai.key', 'google-key-that-must-not-be-used');
        Http::fake();

        $embedding = (new AssistantAiService())->embedText('evidencia documental');

        $this->assertNull($embedding);
        Http::assertNothingSent();
    }

    public function test_image_extraction_uses_the_groq_vision_model(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.groq.key', 'groq-key');
        config()->set('services.groq.vision_model', 'qwen/qwen3.8-27b');
        config()->set('services.groq.url', 'https://api.groq.test/openai/v1/chat/completions');
        Http::fake([
            'api.groq.test/*' => Http::response([
                'choices' => [['message' => ['content' => 'Factura 001-00042']]],
            ]),
        ]);

        $text = (new AssistantAiService())->extractDocumentText('image/png', 'binary-image');

        $this->assertSame('Factura 001-00042', $text);
        Http::assertSent(function ($request): bool {
            $content = $request['messages'][0]['content'];

            return $request['model'] === 'qwen/qwen3.8-27b'
                && $content[1]['type'] === 'image_url'
                && str_starts_with($content[1]['image_url']['url'], 'data:image/png;base64,');
        });
    }

    public function test_pdf_extraction_asks_gemini_for_visual_ocr_with_page_markers(): void
    {
        config()->set('services.ai.provider', 'google');
        config()->set('services.google_ai.key', 'google-key');
        config()->set('services.google_ai.model', 'gemini-test');
        config()->set('services.google_ai.url', 'https://gemini.test/models');
        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => "[[PAGINA:1]]\nACTA 001-2026"]]],
                ]],
            ]),
        ]);

        $text = (new AssistantAiService())->extractDocumentText('application/pdf', 'pdf-binario');

        $this->assertSame("[[PAGINA:1]]\nACTA 001-2026", $text);
        Http::assertSent(function ($request): bool {
            $parts = $request['contents'][0]['parts'];

            return str_contains($parts[0]['text'], 'Realiza OCR')
                && str_contains($parts[0]['text'], '[[PAGINA:N]]')
                && $parts[1]['inline_data']['mime_type'] === 'application/pdf'
                && base64_decode($parts[1]['inline_data']['data'], true) === 'pdf-binario';
        });
    }

    public function test_the_complete_pvl_schema_is_converted_for_groq_strict_mode(): void
    {
        config()->set('services.ai.provider', 'groq');
        config()->set('services.groq.key', 'groq-key');
        config()->set('services.groq.url', 'https://api.groq.test/openai/v1/chat/completions');
        Http::fake([
            'api.groq.test/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'data' => ['pvl' => [], 'racion_a' => null],
                    ])],
                ]],
            ]),
        ]);

        (new PvlAiReportService(new AssistantAiService(), new PvlReportDataMapper()))
            ->analyze('PVL', 2026, 9, ['pvl' => []], []);

        $request = Http::recorded()[0][0];
        $schema = $request['response_format']['json_schema']['schema'];
        $encoded = json_encode($schema);

        $this->assertStringNotContainsString('"OBJECT"', $encoded);
        $this->assertStringNotContainsString('"ARRAY"', $encoded);
        $this->assertStringNotContainsString('"nullable"', $encoded);
        $this->assertStrictObjectSchemas($schema);
    }

    private function assertStrictObjectSchemas(array $schema): void
    {
        $types = (array) ($schema['type'] ?? []);
        if (in_array('object', $types, true)) {
            $properties = $schema['properties'] ?? [];
            $this->assertSame(array_keys($properties), $schema['required'] ?? null);
            $this->assertFalse($schema['additionalProperties'] ?? true);
        }

        foreach ($schema as $value) {
            if (! is_array($value)) {
                continue;
            }

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $this->assertStrictObjectSchemas($item);
                    }
                }
            } else {
                $this->assertStrictObjectSchemas($value);
            }
        }
    }
}
