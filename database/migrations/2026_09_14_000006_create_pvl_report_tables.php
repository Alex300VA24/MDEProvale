<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pvl_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 60);
            $table->char('period', 7);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_reference', 160)->nullable();
            $table->string('file_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->char('file_hash', 64);
            $table->longText('file_data');
            $table->string('index_status', 30)->default('PENDIENTE');
            $table->text('index_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period', 'document_type']);
            $table->index('file_hash');
        });

        Schema::create('pvl_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pvl_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_number')->nullable();
            $table->unsignedInteger('chunk_index');
            $table->mediumText('content');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['pvl_document_id', 'chunk_index'], 'pvl_document_chunk_unique');
            $table->index(['pvl_document_id', 'page_number']);
        });

        Schema::create('pvl_report_runs', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 20);
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('status', 30)->default('BORRADOR');
            $table->json('input_snapshot_json')->nullable();
            $table->json('ai_output_json')->nullable();
            $table->json('validated_data_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->json('sources_json')->nullable();
            $table->string('model_used')->nullable();
            $table->string('prompt_version', 50);
            $table->char('source_fingerprint', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'month', 'report_type']);
            $table->index(['created_by', 'status']);
            $table->index('source_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pvl_report_runs');
        Schema::dropIfExists('pvl_document_chunks');
        Schema::dropIfExists('pvl_documents');
    }
};
