<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPeriodToResponsiblesTable extends Migration
{
    public function up()
    {
        Schema::table('responsibles', function (Blueprint $table) {
            $table->timestamp('start_date')->nullable()->after('active');
            $table->timestamp('end_date')->nullable()->after('start_date');
            $table->index(['type', 'active']);
        });

        // Backfill: el inicio del periodo es cuando se creó la fila; los
        // responsables ya inactivos terminaron su periodo en su última
        // actualización (updateResponsible los marca active=false por lote).
        DB::table('responsibles')->update(['start_date' => DB::raw('created_at')]);
        DB::table('responsibles')->where('active', false)->update(['end_date' => DB::raw('updated_at')]);
    }

    public function down()
    {
        Schema::table('responsibles', function (Blueprint $table) {
            $table->dropIndex(['type', 'active']);
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
}
