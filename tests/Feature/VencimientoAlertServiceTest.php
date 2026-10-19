<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Notification;
use App\Models\Resolution;
use App\Models\State;
use App\Services\Alertas\VencimientoAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VencimientoAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private function resolutionEndingIn(int $days): Resolution
    {
        $current = State::firstOrCreate(['abbreviation' => State::CURRENT], ['title' => 'Vigente']);
        State::firstOrCreate(['abbreviation' => State::EXPIRED], ['title' => 'Vencido']);

        $now = now();
        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local', 'created_at' => $now, 'updated_at' => $now]);

        $resolution = Resolution::create([
            'document' => 'RA-001-2026',
            'date_document' => now()->subYear()->toDateString(),
            'date_start' => now()->subYear()->toDateString(),
            'date_end' => now()->addDays($days)->toDateString(),
        ]);

        Association::forceCreate([
            'code' => 'C-1',
            'name' => 'Comité Uno',
            'address' => 'Jr. Uno 123',
            'company_name' => 'Comité Uno',
            'place_sector_id' => 1,
            'type_premises_id' => 1,
            'state_id' => $current->id,
            'resolution_id' => $resolution->id,
        ]);

        return $resolution;
    }

    public function test_notifies_without_ai_when_provider_is_disabled(): void
    {
        config()->set('services.ai.provider', 'none');
        $this->resolutionEndingIn(5);

        $result = app(VencimientoAlertService::class)->scanAndNotify();

        $this->assertSame(1, $result['resoluciones']);
        $this->assertSame(0, $result['con_ia']);
        $notification = Notification::where('type', 'alerta_resolucion')->firstOrFail();
        $this->assertStringContainsString('vence en 5 día(s)', $notification->title);
        $this->assertArrayNotHasKey('ia', $notification->metadata);
    }

    public function test_is_idempotent_for_same_threshold(): void
    {
        config()->set('services.ai.provider', 'none');
        $this->resolutionEndingIn(5);

        app(VencimientoAlertService::class)->scanAndNotify();
        $second = app(VencimientoAlertService::class)->scanAndNotify();

        $this->assertSame(0, $second['resoluciones']);
        $this->assertSame(1, Notification::where('type', 'alerta_resolucion')->count());
    }

    public function test_ai_failure_does_not_block_the_alert(): void
    {
        config()->set('services.ai.provider', 'google');
        config()->set('services.ai.structured_retries', 1);
        config()->set('services.google_ai.key', 'k');
        config()->set('services.google_ai.url', 'https://gemini.test/models');
        Http::fake(['gemini.test/*' => Http::response(['error' => ['message' => 'boom']], 500)]);
        $this->resolutionEndingIn(5);

        $result = app(VencimientoAlertService::class)->scanAndNotify();

        $this->assertSame(1, $result['resoluciones']);
        $this->assertSame(0, $result['con_ia']);
    }

    public function test_ai_insight_is_added_when_available(): void
    {
        config()->set('services.ai.provider', 'google');
        config()->set('services.google_ai.key', 'k');
        config()->set('services.google_ai.url', 'https://gemini.test/models');
        Http::fake(['gemini.test/*' => Http::response([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => json_encode([
                    'resumen' => 'Vence pronto.',
                    'accion_sugerida' => 'Gestionar la renovación.',
                    'prioridad' => 'alta',
                ])]]],
            ]],
        ])]);
        $this->resolutionEndingIn(5);

        $result = app(VencimientoAlertService::class)->scanAndNotify();

        $this->assertSame(1, $result['con_ia']);
        $this->assertSame('alta', Notification::first()->metadata['ia']['prioridad']);
    }
}
