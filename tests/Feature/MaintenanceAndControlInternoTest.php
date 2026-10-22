<?php

namespace Tests\Feature;

use App\Repositories\Contracts\ControlInternoRepositoryInterface;
use App\Repositories\Contracts\DataMaintenanceRepositoryInterface;
use App\Services\ControlInternoService;
use App\Services\Maintenance\LegacyDateBackfillService;
use App\Services\Maintenance\RagReindexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceAndControlInternoTest extends TestCase
{
    use RefreshDatabase;

    public function test_control_interno_repository_resolves_and_returns_empty_results(): void
    {
        $control = app(ControlInternoRepositoryInterface::class);

        $this->assertSame([], $control->detailProductIdsByKeywords(['LECHE']));
        $this->assertCount(0, $control->responsibles());
        $this->assertNull($control->activeStorekeeper());
    }

    public function test_tarjeta_data_for_an_empty_month_has_the_expected_shape(): void
    {
        $data = app(ControlInternoService::class)->cardData('milk', 2026, 4);

        $this->assertSame('milk', $data['product']);
        $this->assertSame('Abril', $data['month']);
        $this->assertSame([], $data['rows']);
        $this->assertSame([], $data['responsables']);
    }

    public function test_legacy_date_backfill_dry_run_changes_nothing_on_an_empty_database(): void
    {
        $out = new class implements \App\Services\Maintenance\ProgressReporter {
            public function info(string $message): void {}
            public function warn(string $message): void {}
            public function error(string $message): void {}
            public function line(string $message): void {}
            public function newLine(int $count = 1): void {}
            public function table(array $headers, array $rows): void {}
            public function progressStart(int $total): void {}
            public function progressAdvance(): void {}
            public function progressFinish(): void {}
        };

        $result = app(LegacyDateBackfillService::class)->run(true, $out);

        $this->assertSame(['partners' => 0, 'histories' => 0, 'skipped' => 0], $result);
    }

    public function test_rag_reindex_summary_lists_the_three_pipelines(): void
    {
        $summary = app(RagReindexService::class)->summary();

        $this->assertSame(['kb', 'pvl', 'normativa'], array_column($summary, 0));
        $this->assertSame(0, app(DataMaintenanceRepositoryInterface::class)->indexedDocumentCount('kb'));
    }
}
