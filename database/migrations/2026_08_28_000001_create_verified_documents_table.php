<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verified_documents', function (Blueprint $table) {
            $table->id();
            $table->char('token', 64)->unique();
            $table->string('type', 40)->index();
            $table->string('identifier', 100)->unique();
            $table->string('status', 20)->default('vigente')->index();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('storage_path')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verified_documents');
    }
};
