<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompositeIndexToNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Composite index for optimized queries: where('requested_by', ...)->where('is_seen', ...)
            // Also including created_at as requested for potentially faster sorting/filtering
            $table->index(['requested_by', 'is_seen', 'created_at'], 'notifications_reqby_seen_created_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_reqby_seen_created_index');
        });
    }
}
