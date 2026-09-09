<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantChatApiTest extends TestCase
{
    private const ENDPOINT = '/api/asistente/chat';

    public function test_guest_cannot_use_the_assistant(): void
    {
        $this->postJson(self::ENDPOINT, [
            'mensajes' => [['role' => 'user', 'content' => '¿Cómo registro una pecosa?']],
        ])->assertUnauthorized();
    }

    public function test_it_sends_a_scoped_conversation_to_groq(): void
    {
        config([
            'services.groq.key' => 'test-key',
            'services.groq.model' => 'openai/gpt-oss-120b',
            'services.groq.url' => 'https://api.groq.test/openai/v1/chat/completions',
        ]);

        Http::fake([
            'api.groq.test/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => "1. Ingresa a Productos y Pecosas.\n2. Selecciona Registrar Pecosa."],
                ]],
            ]),
        ]);

        $user = User::factory()->make(['id' => 99]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => '¿Cómo registro una pecosa?']],
            ])
            ->assertOk()
            ->assertJsonPath('respuesta', "1. Ingresa a Productos y Pecosas.\n2. Selecciona Registrar Pecosa.");

        Http::assertSent(function ($request) {
            $messages = $request['messages'];

            return $request->url() === 'https://api.groq.test/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'openai/gpt-oss-120b'
                && $request['max_completion_tokens'] === 600
                && $messages[0]['role'] === 'system'
                && str_contains($messages[0]['content'], 'Solo responde consultas')
                && $messages[1]['role'] === 'user';
        });
    }

    public function test_it_rejects_invalid_or_oversized_history(): void
    {
        $user = User::factory()->make(['id' => 100]);
        $messages = array_fill(0, 21, ['role' => 'user', 'content' => 'Ayuda']);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, ['mensajes' => $messages])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mensajes');
    }

    public function test_it_guides_common_tasks_without_an_api_key(): void
    {
        config(['services.groq.key' => null]);
        $user = User::factory()->make(['id' => 101]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => '¿Cómo puedo ver el padrón de socios y beneficiarios?']],
            ])
            ->assertOk()
            ->assertJsonPath('respuesta', fn ($answer) => str_contains($answer, 'Generar Padrón'));
    }

    public function test_it_explains_where_to_find_the_committee_with_most_beneficiaries(): void
    {
        config(['services.groq.key' => null]);
        $user = User::factory()->make(['id' => 102]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => 'como puedo consultar el comite con mas beneficiarios?']],
            ])
            ->assertOk()
            ->assertJsonPath('respuesta', fn ($answer) => str_contains($answer, 'Top Comités'));
    }

    public function test_it_distinguishes_the_club_committee_register_from_the_beneficiary_register(): void
    {
        config(['services.groq.key' => null]);
        $user = User::factory()->make(['id' => 104]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => '¿Cómo genero el padrón de Clubes de Madres por comité?']],
            ])
            ->assertOk()
            ->assertJsonPath('respuesta', fn ($answer) => str_contains($answer, 'Más acciones'));
    }

    public function test_it_returns_available_help_when_the_question_is_unknown_and_no_key_exists(): void
    {
        config(['services.groq.key' => null]);
        $user = User::factory()->make(['id' => 103]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => 'Necesito ayuda']],
            ])
            ->assertOk()
            ->assertJsonPath('respuesta', fn ($answer) => str_contains($answer, 'Puedo guiarte paso a paso'));
    }

    public function test_it_reports_the_remaining_quota_and_blocks_after_five_questions(): void
    {
        config(['services.groq.key' => null]);
        $user = User::factory()->make(['id' => 555]);

        $first = $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => 'Necesito ayuda']],
            ])
            ->assertOk()
            ->assertJsonPath('limite.total', 5)
            ->assertJsonPath('limite.restantes', 4);

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($user)
                ->postJson(self::ENDPOINT, [
                    'mensajes' => [['role' => 'user', 'content' => 'Necesito ayuda']],
                ])
                ->assertOk();
        }

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => 'Necesito ayuda']],
            ])
            ->assertStatus(429)
            ->assertJsonStructure(['mensaje', 'reinicia_en', 'limite' => ['restantes', 'total', 'reinicia_en']])
            ->assertJsonPath('limite.restantes', 0);
    }

    public function test_it_returns_a_view_action_when_a_report_is_explicitly_requested(): void
    {
        config(['services.groq.key' => null]);
        $user = User::factory()->make(['id' => 557]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => 'Genérame un reporte de pecosas de este mes']],
            ])
            ->assertOk()
            ->assertJsonPath('accion.tipo', 'reporte')
            ->assertJsonPath('accion.url', fn ($url) => str_contains($url, '/reportes/generar') && str_contains($url, 'entidades'));
    }

    public function test_a_multi_entity_report_request_is_answered_with_guidance(): void
    {
        config(['services.groq.key' => null]);
        $user = User::factory()->make(['id' => 558]);

        $this->actingAs($user)
            ->postJson(self::ENDPOINT, [
                'mensajes' => [['role' => 'user', 'content' => 'Quiero un reporte que combine comités y productos']],
            ])
            ->assertOk()
            ->assertJsonPath('accion', null)
            ->assertJsonPath('respuesta', fn ($answer) => str_contains($answer, 'varios pasos'));
    }
}
