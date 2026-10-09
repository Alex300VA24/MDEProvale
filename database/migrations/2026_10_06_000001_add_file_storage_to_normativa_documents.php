<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normativa_documents', function (Blueprint $table) {
            $table->string('file_name', 255)->nullable()->after('pdf_url');
            $table->string('mime_type', 100)->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->char('file_hash', 64)->nullable()->after('file_size');
            $table->string('file_path', 255)->nullable()->after('file_hash');

            $table->index('file_hash');
        });
    }

    public function down(): void
    {
        Schema::table('normativa_documents', function (Blueprint $table) {
            $table->dropIndex(['file_hash']);
            $table->dropColumn([
                'file_name',
                'mime_type',
                'file_size',
                'file_hash',
                'file_path',
            ]);
        });
    }
};
