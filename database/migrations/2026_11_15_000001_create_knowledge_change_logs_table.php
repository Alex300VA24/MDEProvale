<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30)->index();      // portal | beneficiarios | resoluciones | sistema
            $table->string('event_type', 60)->index();  // norma_nueva | limite_edad | fin_beneficio | resolucion_por_vencer ...
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title', 200);
            $table->text('summary')->nullable();
            $table->string('priority', 10)->nullable();
            $table->json('details')->nullable();
            $table->unsignedBigInteger('notification_id')->nullable()->index();
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_change_logs');
    }
};
