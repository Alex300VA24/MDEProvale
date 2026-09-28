<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY status ENUM('pending', 'approved', 'rejected', 'informativo') NOT NULL DEFAULT 'pending'");
        DB::statement('ALTER TABLE notifications MODIFY user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE notifications SET status = 'pending' WHERE status = 'informativo'");
        DB::statement("ALTER TABLE notifications MODIFY status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        DB::statement('ALTER TABLE notifications MODIFY user_id BIGINT UNSIGNED NOT NULL');
    }
};
