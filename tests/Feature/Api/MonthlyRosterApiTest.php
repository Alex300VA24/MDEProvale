<?php

namespace Tests\Feature\Api;

use App\Models\MonthlyRoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class MonthlyRosterApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
    }

    public function test_monthly_roster_can_be_copied_closed_and_revised_without_losing_versions(): void
    {
        $october = MonthlyRoster::create([
            'period' => '2026-10-01',
            'version' => 1,
            'status' => MonthlyRoster::CLOSED,
            'closed_at' => now(),
        ]);
        $october->associations()->create([
            'association_id' => null,
            'partner_count' => 2,
            'beneficiary_count' => 3,
            'president_name' => 'Presidenta Histórica',
            'association_snapshot' => ['code' => '001', 'name' => 'Comité de prueba'],
        ]);

        $create = $this->actingAs($this->adminUser())->postJson('/api/dashboard/socios-beneficiarios/monthly-rosters', [
            'period' => '2026-11',
            'source_roster_id' => $october->id,
        ])->assertCreated();

        $novemberId = (int) $create->json('data.id');
        $this->assertDatabaseHas('monthly_roster_associations', [
            'monthly_roster_id' => $novemberId,
            'beneficiary_count' => 3,
        ]);

        $this->actingAs($this->adminUser())
            ->getJson('/api/dashboard/socios-beneficiarios/monthly-rosters')
            ->assertOk()
            ->assertJsonPath('data.0.created_by', 'Test Usuario Prueba');

        $this->actingAs($this->adminUser())
            ->postJson("/api/dashboard/socios-beneficiarios/monthly-rosters/{$novemberId}/close")
            ->assertOk()
            ->assertJsonPath('data.status', MonthlyRoster::CLOSED);

        $revision = $this->actingAs($this->adminUser())->postJson(
            "/api/dashboard/socios-beneficiarios/monthly-rosters/{$novemberId}/revise",
            ['reason' => 'Corrección de una beneficiaria registrada en otro comité.']
        )->assertCreated();

        $revisionId = (int) $revision->json('data.id');
        $this->assertDatabaseHas('monthly_rosters', [
            'id' => $revisionId,
            'period' => '2026-11-01',
            'version' => 2,
            'status' => MonthlyRoster::DRAFT,
            'source_roster_id' => $novemberId,
        ]);

        $this->actingAs($this->adminUser())
            ->postJson("/api/dashboard/socios-beneficiarios/monthly-rosters/{$revisionId}/close")
            ->assertOk();

        $this->assertDatabaseHas('monthly_rosters', ['id' => $novemberId, 'status' => MonthlyRoster::SUPERSEDED]);
        $this->assertDatabaseHas('monthly_rosters', ['id' => $revisionId, 'status' => MonthlyRoster::CLOSED]);
    }

    public function test_closed_roster_cannot_be_synchronized_directly(): void
    {
        $roster = MonthlyRoster::create([
            'period' => '2026-10-01',
            'version' => 1,
            'status' => MonthlyRoster::CLOSED,
            'closed_at' => now(),
        ]);

        $this->actingAs($this->adminUser())
            ->postJson("/api/dashboard/socios-beneficiarios/monthly-rosters/{$roster->id}/sync")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roster');
    }

    public function test_draft_detail_can_be_viewed_and_deleted_with_its_snapshot(): void
    {
        $roster = MonthlyRoster::create([
            'period' => '2026-11-01',
            'version' => 1,
            'status' => MonthlyRoster::DRAFT,
        ]);
        $association = $roster->associations()->create([
            'association_id' => null,
            'partner_count' => 2,
            'beneficiary_count' => 3,
            'president_name' => 'Presidenta de Prueba',
            'association_snapshot' => ['code' => '001', 'name' => 'Comité de prueba'],
        ]);
        $entry = $association->entries()->create([
            'partner_id' => null,
            'beneficiary_id' => null,
            'beneficiary_history_id' => null,
            'partner_snapshot' => ['names' => 'Socia'],
            'beneficiary_snapshot' => ['names' => 'Beneficiaria'],
        ]);

        $this->actingAs($this->adminUser())
            ->getJson("/api/dashboard/socios-beneficiarios/monthly-rosters/{$roster->id}")
            ->assertOk()
            ->assertJsonPath('data.associations.0.name', 'Comité de prueba')
            ->assertJsonPath('data.associations.0.beneficiary_count', 3);

        $this->actingAs($this->adminUser())
            ->deleteJson("/api/dashboard/socios-beneficiarios/monthly-rosters/{$roster->id}")
            ->assertOk();

        $this->assertDatabaseMissing('monthly_rosters', ['id' => $roster->id]);
        $this->assertDatabaseMissing('monthly_roster_associations', ['id' => $association->id]);
        $this->assertDatabaseMissing('monthly_roster_entries', ['id' => $entry->id]);
    }

    public function test_closed_roster_cannot_be_deleted(): void
    {
        $roster = MonthlyRoster::create([
            'period' => '2026-10-01',
            'version' => 1,
            'status' => MonthlyRoster::CLOSED,
            'closed_at' => now(),
        ]);

        $this->actingAs($this->adminUser())
            ->deleteJson("/api/dashboard/socios-beneficiarios/monthly-rosters/{$roster->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roster');
    }
}
