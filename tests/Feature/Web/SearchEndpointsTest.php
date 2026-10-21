<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class SearchEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();

        $now = now();
        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('people')->insert([
            'id' => 1, 'names' => 'Sharon', 'father_lastname' => 'Vasquez', 'mother_lastname' => 'Rojas',
            'dni' => '12345678', 'gender' => 'F', 'birthdate' => '1990-01-01', 'address' => 'Calle 1',
            'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function test_people_search_matches_words_in_any_name_column(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson('/api/search/people?q=sharon vasquez')
            ->assertOk()
            ->assertJsonPath('results.0.id', 1)
            ->assertJsonPath('results.0.text', 'Sharon Vasquez Rojas (12345678)');

        $this->actingAs($this->adminUser())
            ->getJson('/api/search/people?q=zzzz')
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_short_query_returns_unfiltered_results(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson('/api/search/people?q=s')
            ->assertOk()
            ->assertJsonCount(1, 'results');
    }
}
