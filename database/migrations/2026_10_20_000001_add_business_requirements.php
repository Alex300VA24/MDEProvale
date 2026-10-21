<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obstetric_data', function (Blueprint $table) {
            $table->foreignId('child_beneficiary_id')
                ->nullable()
                ->after('beneficiary_history_id')
                ->constrained('beneficiaries')
                ->nullOnDelete();
        });

        Schema::table('beneficiary_histories', function (Blueprint $table) {
            $table->index(['state_id', 'date_end', 'type_benefit_id'], 'benefit_expiry_lookup');
        });

        Schema::table('associations', function (Blueprint $table) {
            $table->string('state_source', 10)->default('AUTOMATIC')->after('state_id');
        });

        Schema::create('association_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('to_state_id')->constrained('states');
            $table->string('reason', 50);
            $table->text('notes')->nullable();
            $table->date('effective_date');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['association_id', 'effective_date']);
        });

        Schema::create('system_audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 190)->unique();
            $table->string('event_type', 80);
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->string('source', 20)->default('SYSTEM');
            $table->foreignId('from_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('to_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('reason_code', 80)->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['event_type', 'occurred_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('dedupe_key', 190)->nullable()->after('type')->unique();
        });

        Schema::table('document_attachments', function (Blueprint $table) {
            $table->string('storage_disk', 40)->nullable()->after('file_size');
            $table->string('file_path')->nullable()->after('storage_disk');
            $table->string('checksum', 64)->nullable()->after('file_path');
            $table->foreignId('uploaded_by')->nullable()->after('checksum')->constrained('users')->nullOnDelete();
            $table->index(['document_type', 'attachable_type', 'attachable_id'], 'attachment_type_lookup');
        });
        DB::statement('ALTER TABLE document_attachments MODIFY file_data LONGTEXT NULL');

        Schema::table('supervision_off_days', function (Blueprint $table) {
            $table->string('reason', 255)->nullable()->change();
        });

        DB::table('settings')->updateOrInsert(
            ['key' => 'uploads.max_attachment_mb'],
            ['value' => '10', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('type_benefits')->where('abbreviation', 'ADU')->update([
            'min_age' => 66,
            'updated_at' => now(),
        ]);

        $moduleId = DB::table('modules')->where('slug', 'evaluacion-nutricional')->value('id');
        if (! $moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'Evaluación Nutricional',
                'slug' => 'evaluacion-nutricional',
                'description' => 'Evaluación nutricional y clasificación poblacional',
                'icon' => 'fa-heart-pulse',
                'route' => 'evaluacion-nutricional',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sourceModuleId = DB::table('modules')->where('slug', 'socios-beneficiarios')->value('id');
        if ($sourceModuleId) {
            $permissions = DB::table('module_rol')->where('module_id', $sourceModuleId)->get();
            foreach ($permissions as $permission) {
                DB::table('module_rol')->updateOrInsert(
                    ['module_id' => $moduleId, 'rol_id' => $permission->rol_id],
                    [
                        'can_view' => $permission->can_view,
                        'can_create' => $permission->can_create,
                        'can_edit' => $permission->can_edit,
                        'can_delete' => $permission->can_delete,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'evaluacion-nutricional')->value('id');
        if ($moduleId) {
            DB::table('module_rol')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }

        DB::table('settings')->where('key', 'uploads.max_attachment_mb')->delete();

        Schema::table('supervision_off_days', function (Blueprint $table) {
            $table->string('reason', 60)->nullable()->change();
        });

        Schema::table('document_attachments', function (Blueprint $table) {
            $table->dropIndex('attachment_type_lookup');
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['storage_disk', 'file_path', 'checksum']);
        });
        DB::statement("UPDATE document_attachments SET file_data = '' WHERE file_data IS NULL");
        DB::statement('ALTER TABLE document_attachments MODIFY file_data LONGTEXT NOT NULL');

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn('dedupe_key');
        });

        Schema::dropIfExists('system_audit_events');
        Schema::dropIfExists('association_status_changes');

        Schema::table('associations', function (Blueprint $table) {
            $table->dropColumn('state_source');
        });

        Schema::table('beneficiary_histories', function (Blueprint $table) {
            $table->dropIndex('benefit_expiry_lookup');
        });

        Schema::table('obstetric_data', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_beneficiary_id');
        });
    }
};
