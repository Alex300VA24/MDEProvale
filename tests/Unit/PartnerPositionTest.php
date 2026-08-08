<?php

namespace Tests\Unit;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_load_its_position(): void
    {
        Schema::create('positions', function ($table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('partners', function ($table) {
            $table->id();
            $table->foreignId('position_id')->nullable()->constrained();
            $table->timestamps();
        });

        $positionId = DB::table('positions')->insertGetId([
            'title' => 'PRESIDENTA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partnerId = DB::table('partners')->insertGetId([
            'position_id' => $positionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partner = Partner::find($partnerId);

        $this->assertNotNull($partner);
        $this->assertNotNull($partner->position);
        $this->assertSame('PRESIDENTA', $partner->position->title);
    }
}
