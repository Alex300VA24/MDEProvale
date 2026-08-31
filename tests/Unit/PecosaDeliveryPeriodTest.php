<?php

namespace Tests\Unit;

use App\Models\Pecosa;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PecosaDeliveryPeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_period_does_not_advance_during_the_last_week_of_the_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 12:00:00'));

        $this->assertSame([2026, 8], Pecosa::currentDeliveryPeriod());
    }

    public function test_late_month_pecosa_still_belongs_to_the_following_delivery_period(): void
    {
        $effective = Pecosa::effectiveDeliveryDate('2026-07-30');

        $this->assertSame([2026, 8], [$effective->year, $effective->month]);
    }
}
