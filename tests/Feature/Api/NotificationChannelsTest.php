<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Services\KnowledgeBase\KnowledgeChangeLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\FlujoComiteFixtures;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;
    use FlujoComiteFixtures;

    private const API = '/api/dashboard/sistema/notifications';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFlujo();
    }

    private function notice(string $type, string $status = Notification::STATUS_OPEN, array $metadata = []): Notification
    {
        return Notification::create([
            'type' => $type,
            'title' => $type,
            'status' => $status,
            'user_id' => 1,
            'requested_at' => now(),
            'is_seen' => false,
            'metadata' => $metadata,
        ]);
    }

    public function test_bell_and_inbox_lists_and_counts_are_separate(): void
    {
        $this->notice('normativa_pvl');
        $this->notice('alerta_beneficiarios');
        $this->notice('alerta_resolucion', Notification::STATUS_OPEN);

        $admin = $this->adminUser();

        $this->actingAs($admin)->getJson(self::API.'?channel=inbox')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($admin)->getJson(self::API.'?channel=bell')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'alerta_resolucion');

        $this->actingAs($admin)->getJson(self::API.'/unread-count?channel=inbox')->assertJsonPath('count', 2);
        $this->actingAs($admin)->getJson(self::API.'/unread-count?channel=bell')->assertJsonPath('count', 1);
    }

    public function test_marking_inbox_seen_does_not_clear_bell(): void
    {
        $this->notice('normativa_pvl');
        $this->notice('regularizacion');

        $admin = $this->adminUser();
        $this->actingAs($admin)->postJson(self::API.'/mark-seen?channel=inbox')->assertOk();

        // Visto no deja de contar: solo se descuenta al subsanar.
        $this->actingAs($admin)->getJson(self::API.'/unread-count?channel=inbox')->assertJsonPath('count', 1);
        $this->actingAs($admin)->getJson(self::API.'/unread-count?channel=bell')->assertJsonPath('count', 1);
    }

    public function test_solving_verifies_real_state_before_clearing_the_count(): void
    {
        $admin = $this->adminUser();
        $norma = $this->notice('normativa_pvl');
        $gone = $this->notice('alerta_resolucion', Notification::STATUS_OPEN, ['resolution_id' => 999999]);
        $pendiente = $this->notice('regularizacion', Notification::STATUS_OPEN, [
            'resolution_id' => $this->resolucion('PEN'),
        ]);

        $this->actingAs($admin)->postJson(self::API.'/mark-seen')->assertOk();
        $this->actingAs($admin)->getJson(self::API.'/unread-count')->assertJsonPath('count', 3);

        // Problema ya inexistente: se acepta.
        $this->actingAs($admin)->postJson(self::API."/{$gone->id}/solve")->assertOk()->assertJsonPath('data.status', 'solucionado');
        $this->actingAs($admin)->postJson(self::API."/{$norma->id}/solve")->assertOk();

        // Resolución todavía pendiente: la verificación lo rechaza y sigue contando.
        $this->actingAs($admin)->postJson(self::API."/{$pendiente->id}/solve")->assertStatus(422);
        $this->actingAs($admin)->getJson(self::API.'/unread-count')->assertJsonPath('count', 1);
    }

    public function test_history_endpoint_is_admin_only_and_lists_records(): void
    {
        app(KnowledgeChangeLogService::class)->record('portal', 'norma_nueva', 'Resolución 001-2026', 'Resumen');

        $this->getJson('/api/dashboard/base-conocimiento/historial')->assertUnauthorized();

        $this->actingAs($this->adminUser())
            ->getJson('/api/dashboard/base-conocimiento/historial')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Resolución 001-2026')
            ->assertJsonPath('meta.total', 1);
    }
}
