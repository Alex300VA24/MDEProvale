<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->updateOrInsert(
            ['slug' => 'base-conocimiento'],
            [
                'name' => 'Base de Conocimiento IA',
                'description' => 'Documentos indexados con IA para consultas tipo RAG',
                'icon' => 'fa-database',
                'route' => 'base-conocimiento',
                'order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('modules')->where('slug', 'base-conocimiento')->delete();
    }
};
