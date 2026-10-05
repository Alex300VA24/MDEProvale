<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kb_documents', function (Blueprint $table) {
            $table->string('file_path', 255)->nullable()->after('file_hash');
            $table->index('index_status');
        });

        Schema::table('pvl_documents', function (Blueprint $table) {
            $table->string('file_path', 255)->nullable()->after('file_hash');
            $table->index('index_status');
        });

        Schema::table('normativa_documents', function (Blueprint $table) {
            $table->index('index_status');
        });

        // FULLTEXT para el prefiltrado léxico (MySQL 5.7+/InnoDB).
        // Si el motor no lo soporta, la migración sigue funcionando
        // porque el search usa LIKE como fallback.
        try {
            Schema::table('kb_document_chunks', function (Blueprint $table) {
                $table->fullText('content');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('pvl_document_chunks', function (Blueprint $table) {
                $table->fullText('content');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('normativa_document_chunks', function (Blueprint $table) {
                $table->fullText('content');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::table('kb_documents', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::table('pvl_documents', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
