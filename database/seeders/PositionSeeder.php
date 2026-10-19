<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (Position::COMMITTEE_TITLES as $title) {
            DB::table('positions')->updateOrInsert(
                ['title' => $title],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
