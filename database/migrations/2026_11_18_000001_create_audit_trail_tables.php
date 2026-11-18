<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad: registra qué usuario cambió qué, cuándo y desde dónde.
 * Consultado por el apartado "Historial de Cambios".
 *
 *  - audit_logs:         un evento por acción (crear, editar, eliminar, etc.)
 *  - audit_log_changes:  detalle campo a campo (valor anterior / nuevo)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Quién. Se guarda copia (snapshot) para conservar el dato si el usuario se elimina o cambia.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 200)->nullable();
            $table->string('user_username', 100)->nullable();
            $table->string('user_role', 100)->nullable();

            // Qué.
            $table->string('module', 60)->index();          // socios | beneficiarios | comites | padrones | resoluciones | pecosas | usuarios ...
            $table->string('action', 30)->index();          // created | updated | deleted | restored | status_changed | login | logout | export | other
            $table->string('auditable_type', 120)->nullable();  // App\Models\Partner
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('auditable_label', 255)->nullable(); // texto legible: "Socio: Juan Pérez (DNI 12345678)"
            $table->text('description')->nullable();

            // Desde dónde.
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('url', 500)->nullable();

            // Agrupa varios registros hechos en una misma operación.
            $table->uuid('batch_uuid')->nullable()->index();

            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_idx');
            $table->index(['user_id', 'occurred_at'], 'audit_logs_user_date_idx');
            $table->index(['module', 'occurred_at'], 'audit_logs_module_date_idx');
        });

        Schema::create('audit_log_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_log_id')->constrained('audit_logs')->cascadeOnDelete();
            $table->string('field', 100);
            $table->string('field_label', 150)->nullable();
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->timestamps();

            $table->index(['audit_log_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_changes');
        Schema::dropIfExists('audit_logs');
    }
};
