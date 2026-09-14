<?php

namespace Tests\Unit;

use App\Services\ReparticionService;
use PHPUnit\Framework\TestCase;

class ReparticionServiceTest extends TestCase
{
    private array $configuration = [
        'service_days' => 30,
        'milk_grams_per_beneficiary' => 44.0,
        'oat_grams_per_beneficiary' => 51.5,
        'milk_can_grams' => 410.0,
        'oat_bag_grams' => 1000.0,
        'milk_cans_per_box' => 48,
        'oat_kg_per_sack' => 30,
    ];

    public function test_official_formulas_use_commercial_rounding_and_six_decimal_daily_values(): void
    {
        $result = (new ReparticionService())->calculateQuantities(100, $this->configuration);

        $this->assertSame(322, $result['milk_total']);
        $this->assertSame(155, $result['oat_total']);
        $this->assertSame(10.733333, $result['daily_milk']);
        $this->assertSame(5.166667, $result['daily_oat']);
    }

    public function test_package_breakdown_reconstructs_exact_totals(): void
    {
        $result = (new ReparticionService())->calculateQuantities(100, $this->configuration);

        $this->assertSame($result['milk_total'], $result['milk_boxes'] * 48 + $result['milk_loose_cans']);
        $this->assertSame($result['oat_total'], $result['oat_sacks'] * 30 + $result['oat_loose_kg']);
    }

    public function test_days_change_recalculates_every_dependent_quantity(): void
    {
        $service = new ReparticionService();
        $at28Days = $service->calculateQuantities(100, [...$this->configuration, 'service_days' => 28]);
        $at31Days = $service->calculateQuantities(100, [...$this->configuration, 'service_days' => 31]);

        $this->assertNotSame($at28Days['milk_total'], $at31Days['milk_total']);
        $this->assertNotSame($at28Days['oat_total'], $at31Days['oat_total']);
        $this->assertSame($at31Days['milk_total'], $at31Days['milk_boxes'] * 48 + $at31Days['milk_loose_cans']);
        $this->assertSame($at31Days['oat_total'], $at31Days['oat_sacks'] * 30 + $at31Days['oat_loose_kg']);
    }
}
