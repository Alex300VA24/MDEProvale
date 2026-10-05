<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('file_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->char('file_hash', 64);
            $table->longText('file_data');
            $table->string('index_status', 30)->default('PENDIENTE');
            $table->text('index_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('file_hash');
        });

        Schema::create('kb_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_document_id')->constrained('kb_documents')->cascadeOnDelete();
            $table->unsignedInteger('page_number')->nullable();
            $table->unsignedInteger('chunk_index');
            $table->mediumText('content');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['kb_document_id', 'chunk_index'], 'kb_document_chunk_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_document_chunks');
        Schema::dropIfExists('kb_documents');
    }
};
