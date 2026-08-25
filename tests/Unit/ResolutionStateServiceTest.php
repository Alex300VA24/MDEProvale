<?php

namespace Tests\Unit;

use App\Models\Resolution;
use App\Models\State;
use App\Services\ResolutionStateService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ResolutionStateServiceTest extends TestCase
{
    public function test_resolution_is_current_through_end_date(): void
    {
        $resolution = new Resolution(['date_end' => '2026-08-24']);

        $this->assertSame(
            State::CURRENT,
            (new ResolutionStateService())->abbreviationFor($resolution, Carbon::parse('2026-08-24'))
        );
    }

    public function test_resolution_is_expired_after_end_date(): void
    {
        $resolution = new Resolution(['date_end' => '2026-08-24']);

        $this->assertSame(
            State::EXPIRED,
            (new ResolutionStateService())->abbreviationFor($resolution, Carbon::parse('2026-08-25'))
        );
    }
}
