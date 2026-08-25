<?php

use App\Models\State;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('states')) {
            return;
        }

        $ids = DB::table('states')->pluck('id', 'abbreviation');
        $active = $ids[State::ACTIVE] ?? null;
        $inactive = $ids[State::INACTIVE] ?? null;
        $current = $ids[State::CURRENT] ?? null;
        $expired = $ids[State::EXPIRED] ?? null;
        $pending = $ids[State::PENDING] ?? null;

        if (!$active || !$inactive || !$current || !$expired || !$pending) {
            return;
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->where('state_id', $current)->update(['state_id' => $active]);
            DB::table('users')->whereIn('state_id', [$expired, $pending])->update(['state_id' => $inactive]);
        }

        foreach (['partners', 'directives', 'beneficiary_histories', 'products', 'pecosas'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->where('state_id', $active)->update(['state_id' => $current]);
            DB::table($table)->whereIn('state_id', [$inactive, $pending])->update(['state_id' => $expired]);
        }

        if (Schema::hasTable('resolutions')) {
            DB::table('resolutions')->orderBy('id')->chunkById(200, function ($resolutions) use ($current, $expired) {
                foreach ($resolutions as $resolution) {
                    $isExpired = $resolution->date_end
                        && Carbon::parse($resolution->date_end)->startOfDay()->lt(now()->startOfDay());

                    DB::table('resolutions')->where('id', $resolution->id)->update([
                        'state_id' => $isExpired ? $expired : $current,
                    ]);
                }
            });
        }

        if (Schema::hasTable('associations')) {
            app(\App\Services\AssociationStateService::class)->syncAll();
        }
    }

    public function down(): void
    {
        // Normalización de datos no reversible sin conocer semántica anterior.
    }
};
