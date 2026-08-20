<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('modules')
            ->where('slug', 'mantenimiento')
            ->update([
                'name' => 'Responsables y Raciones',
                'slug' => 'responsables-raciones',
                'description' => 'Gestión de responsables del programa y raciones por año',
                'icon' => 'fa-sliders',
                'route' => 'responsables-raciones',
            ]);

        DB::table('rols')
            ->where('title', 'Usuario Principal')
            ->update(['description' => 'Acceso a todos los módulos excepto Responsables y Raciones, Reportes y Sistema']);
    }

    public function down()
    {
        DB::table('modules')
            ->where('slug', 'responsables-raciones')
            ->update([
                'name' => 'Mantenimiento',
                'slug' => 'mantenimiento',
                'description' => 'Gestión de mantenimiento',
                'icon' => 'fa-tools',
                'route' => 'mantenimiento',
            ]);

        DB::table('rols')
            ->where('title', 'Usuario Principal')
            ->update(['description' => 'Acceso a todos los módulos excepto mantenimiento y sistema']);
    }
};
