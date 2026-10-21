<?php

namespace Tests\Unit;

use App\Services\BenefitPeriodCalculator;
use PHPUnit\Framework\TestCase;

class BenefitPeriodCalculatorTest extends TestCase
{
    public function test_ges_ends_nine_months_after_child_birthdate(): void
    {
        $calculator = new BenefitPeriodCalculator();

        $this->assertSame('2027-07-19', $calculator->fromBirthdate('2026-10-20', 'GES'));
        $this->assertSame('2024-10-30', $calculator->fromBirthdate('2024-01-31', 'GES'));
    }

    public function test_lac_ends_two_years_after_child_birthdate(): void
    {
        $calculator = new BenefitPeriodCalculator();

        $this->assertSame('2028-10-19', $calculator->fromBirthdate('2026-10-20', 'LAC'));
        $this->assertSame('2026-02-27', $calculator->fromBirthdate('2024-02-29', 'LAC'));
    }

    public function test_ges_without_registered_birthdate_has_no_end_date_yet(): void
    {
        $calculator = new BenefitPeriodCalculator();

        $this->assertNull($calculator->fromBirthdate(null, 'GES'));
    }
}
