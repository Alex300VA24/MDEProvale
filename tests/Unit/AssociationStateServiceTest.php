<?php

namespace Tests\Unit;

use App\Models\Association;
use App\Models\Resolution;
use App\Models\State;
use App\Services\AssociationStateService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class AssociationStateServiceTest extends TestCase
{
    private AssociationStateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssociationStateService();
    }

    public function test_association_is_current_through_resolution_end_date(): void
    {
        $association = $this->associationEndingOn('2026-08-24');

        $this->assertSame(
            State::CURRENT,
            $this->service->abbreviationFor($association, Carbon::parse('2026-08-24'))
        );
    }

    public function test_association_is_pending_for_one_month_after_expiration(): void
    {
        $association = $this->associationEndingOn('2026-08-24');

        $this->assertSame(
            State::PENDING,
            $this->service->abbreviationFor($association, Carbon::parse('2026-09-24'))
        );
    }

    public function test_association_is_expired_after_pending_month(): void
    {
        $association = $this->associationEndingOn('2026-08-24');

        $this->assertSame(
            State::EXPIRED,
            $this->service->abbreviationFor($association, Carbon::parse('2026-09-25'))
        );
    }

    public function test_association_uses_latest_resolution_from_history(): void
    {
        $primary = new Resolution();
        $primary->id = 1;
        $primary->date_start = '2025-01-01';
        $primary->date_end = '2025-12-31';

        $renewal = new Resolution();
        $renewal->id = 2;
        $renewal->date_start = '2026-01-01';
        $renewal->date_end = '2026-12-31';

        $association = (new Association())
            ->setRelation('resolution', $primary)
            ->setRelation('resolutionsHistory', collect([$renewal]));

        $this->assertSame(
            State::CURRENT,
            $this->service->abbreviationFor($association, Carbon::parse('2026-08-24'))
        );
    }

    private function associationEndingOn(string $date): Association
    {
        $resolution = new Resolution();
        $resolution->date_end = $date;

        return (new Association())->setRelation('resolution', $resolution);
    }
}
