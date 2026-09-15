<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_histories', function (Blueprint $table) {
            $table->boolean('is_malnourished')->default(false)->after('reason_disqualification_id');
            $table->boolean('is_disabled')->default(false)->after('is_malnourished');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_histories', function (Blueprint $table) {
            $table->dropColumn(['is_malnourished', 'is_disabled']);
        });
    }
};
