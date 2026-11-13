<?php

use App\Models\State;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('states') || ! Schema::hasTable('products')) {
            return;
        }

        $ids = DB::table('states')->pluck('id', 'abbreviation');
        if (! isset($ids[State::ACTIVE], $ids[State::INACTIVE], $ids[State::CURRENT], $ids[State::EXPIRED])) {
            return;
        }

        DB::table('products')->where('state_id', $ids[State::CURRENT])->update(['state_id' => $ids[State::ACTIVE]]);
        DB::table('products')->whereIn('state_id', [$ids[State::EXPIRED], $ids[State::INACTIVE]])->update(['state_id' => $ids[State::INACTIVE]]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('states') || ! Schema::hasTable('products')) {
            return;
        }

        $ids = DB::table('states')->pluck('id', 'abbreviation');
        if (! isset($ids[State::ACTIVE], $ids[State::INACTIVE], $ids[State::CURRENT], $ids[State::EXPIRED])) {
            return;
        }

        DB::table('products')->where('state_id', $ids[State::ACTIVE])->update(['state_id' => $ids[State::CURRENT]]);
        DB::table('products')->where('state_id', $ids[State::INACTIVE])->update(['state_id' => $ids[State::EXPIRED]]);
    }
};
