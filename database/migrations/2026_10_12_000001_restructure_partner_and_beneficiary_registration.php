<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('socioeconomic_condition', 30)->nullable()->after('children_count');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority')->default(1)->after('relationship_id');
        });

        DB::table('partners')->where('is_pregnant', true)->update(['socioeconomic_condition' => 'GESTANTE']);
        DB::table('partners')->where('is_pregnant', false)->where('is_lactating', true)->update(['socioeconomic_condition' => 'LACTANTE']);

        DB::table('beneficiaries')->orderBy('id')->get(['id'])->each(function ($beneficiary) {
            $priority = DB::table('beneficiary_histories')
                ->join('type_benefits', 'type_benefits.id', '=', 'beneficiary_histories.type_benefit_id')
                ->where('beneficiary_histories.beneficiary_id', $beneficiary->id)
                ->orderByDesc('beneficiary_histories.date_begin')
                ->value('type_benefits.priority');

            if (in_array((int) $priority, [1, 2], true)) {
                DB::table('beneficiaries')->where('id', $beneficiary->id)->update(['priority' => $priority]);
            }
        });

        foreach (['Hijo', 'Nieto'] as $relationship) {
            if (! DB::table('relationships')->whereRaw('LOWER(title) = ?', [strtolower($relationship)])->exists()) {
                DB::table('relationships')->insert([
                    'title' => $relationship,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn('priority');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('socioeconomic_condition');
        });
    }
};
