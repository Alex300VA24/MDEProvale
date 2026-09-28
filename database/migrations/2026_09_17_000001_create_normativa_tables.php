<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normativa_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            $table->unsignedTinyInteger('tipo_id');
            $table->string('tipo_documento', 60);
            $table->char('periodo', 7)->nullable();
            $table->string('numero', 80)->nullable();
            $table->string('titulo', 255);
            $table->text('asunto')->nullable();
            $table->text('concepto')->nullable();
            $table->date('fecha_documento')->nullable();
            $table->string('pdf_url', 255);
            $table->string('index_status', 30)->default('PENDIENTE');
            $table->text('index_error')->nullable();
            $table->boolean('relevancia_pvl')->nullable();
            $table->text('relevancia_resumen')->nullable();
            $table->text('relevancia_motivo')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['tipo_id', 'fecha_documento']);
            $table->index('relevancia_pvl');
        });

        Schema::create('normativa_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('normativa_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_number')->nullable();
            $table->unsignedInteger('chunk_index');
            $table->mediumText('content');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['normativa_document_id', 'chunk_index'], 'normativa_document_chunk_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normativa_document_chunks');
        Schema::dropIfExists('normativa_documents');
    }
};
