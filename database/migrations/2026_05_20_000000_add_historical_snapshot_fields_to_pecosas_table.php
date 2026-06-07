<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHistoricalSnapshotFieldsToPecosasTable extends Migration
{
    public function up()
    {
        Schema::table('pecosas', function (Blueprint $table) {
            $table->string('chief_dni', 8)->nullable()->after('chief_name');
            $table->string('storekeeper_dni', 8)->nullable()->after('storekeeper_name');
            $table->string('managing_partner_dni', 8)->nullable()->after('managing_partner_name');
            $table->string('president_dni', 8)->nullable()->after('president_name');
            $table->string('association_address', 150)->nullable()->after('association_code');
            $table->string('association_zone_code', 20)->nullable()->after('association_address');
            $table->string('association_zone_name', 100)->nullable()->after('association_zone_code');
            $table->string('association_sector_name', 100)->nullable()->after('association_zone_name');
            $table->unsignedInteger('beneficiaries_count')->nullable()->after('association_sector_name');
        });
    }

    public function down()
    {
        Schema::table('pecosas', function (Blueprint $table) {
            $table->dropColumn([
                'chief_dni',
                'storekeeper_dni',
                'managing_partner_dni',
                'president_dni',
                'association_address',
                'association_zone_code',
                'association_zone_name',
                'association_sector_name',
                'beneficiaries_count',
            ]);
        });
    }
}
